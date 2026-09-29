<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFormalizacion;
use App\Models\SolicitudPaquete;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SolicitudFirmaService
{
    public function requisitos(Solicitud $solicitud, SolicitudPaquete $paquete, User $actor, string $ability): array
    {
        $items = app(SolicitudRequisitosService::class)->evaluar($solicitud, $actor, true, $ability)['requisitos'];
        $items[] = ['clave' => 'firma_qa', 'cumplido' => config('originacion.firmas_qa_habilitadas') === true,
            'mensaje' => 'El administrador técnico debe habilitar ORIGINACION_FIRMAS_QA_HABILITADAS en QA y reconstruir la caché de configuración.'];
        $items[] = ['clave' => 'sucursal', 'cumplido' => (bool) $actor->currentSucursal?->activa
            && (int) $actor->current_sucursal_id === (int) $solicitud->sucursal_id,
            'mensaje' => 'Selecciona una sucursal activa responsable de la solicitud.'];
        $items[] = ['clave' => 'contrato_actual', 'cumplido' => $paquete->solicitud_id === $solicitud->id
            && $paquete->solicitud_resolucion_id === $solicitud->resoluciones()->latest('id')->value('id'),
            'mensaje' => 'Este contrato es histórico. Abre el contrato de la aprobación actual.'];
        $items[] = ['clave' => 'tabla_fiscal', 'cumplido' => ($paquete->snapshot['formato'] ?? null) === 2
            && ($paquete->snapshot['fiscalidad'] ?? null) === 'proyeccion' && ($paquete->snapshot['modo'] ?? null) === 'qa',
            'mensaje' => 'La firma QA requiere contrato y tabla con proyección fiscal. Los documentos anteriores no se convierten: devuelve, revisa y aprueba para preparar un nuevo contrato.'];
        $doc = $paquete->documento;
        $items[] = ['clave' => 'pdf_original', 'cumplido' => $doc?->estado === DocumentoGeneradoEstado::Generado && (bool) $doc?->archivo_hash && $doc?->generado_en !== null,
            'mensaje' => 'Espera a que el PDF original esté generado antes de recibir o aceptar la firma.'];

        return ['requisitos' => $items, 'permitido' => ! collect($items)->contains('cumplido', false)];
    }

    public function recibir(Solicitud $solicitud, SolicitudPaquete $paquete, array $data, UploadedFile $archivo, User $actor): SolicitudFirma
    {
        $path = null;
        $disk = config('documentos.disk');
        try {
            return DB::transaction(function () use ($solicitud, $paquete, $data, $archivo, $actor, $disk, &$path) {
                $solicitud = $this->bloquear($solicitud, $paquete, $actor, 'receiveSignature');
                $hash = hash_file('sha256', $archivo->getRealPath());
                $existing = SolicitudFirma::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ($existing->solicitud_paquete_id !== $paquete->id || $existing->recibida_por !== $actor->id
                        || $existing->archivo_hash !== $hash || $existing->fecha_firma->toDateString() !== $data['fecha_firma']) {
                        $this->error('Este envío ya se registró con otros datos. Actualiza la página.');
                    }

                    return $existing;
                }
                $this->validar($solicitud, $paquete, $actor, 'receiveSignature', $data['lock_version']);
                if ($paquete->firmas()->whereIn('estado', ['recibida', 'aceptada'])->exists()) {
                    $this->error('Ya existe una firma pendiente o aceptada. Revísala antes de recibir otra copia.');
                }
                $this->originalIntacto($paquete);
                if ($hash === $paquete->documento->archivo_hash) {
                    $this->error('Adjuntaste el mismo PDF original. Recibe una copia digitalizada con las firmas; generar el contrato no equivale a firmarlo.');
                }
                $generado = $paquete->documento->generado_en->timezone(app(FechaEmpresa::class)->zonaHoraria())->toDateString();
                if ($data['fecha_firma'] < $generado || $data['fecha_firma'] > app(FechaEmpresa::class)->hoy()->toDateString()) {
                    $this->error('La fecha de firma debe estar entre la generación del contrato y hoy.');
                }
                $path = $archivo->store('solicitudes/'.$solicitud->id.'/firmas', $disk);
                if (! $path) {
                    $this->error('No se pudo guardar el archivo. Intenta nuevamente; no se ha recibido la firma.');
                }
                $firma = $paquete->firmas()->create([
                    'idempotency_key' => $data['idempotency_key'], 'recibida_por' => $actor->id,
                    'fecha_firma' => $data['fecha_firma'], 'disk' => $disk, 'path' => $path,
                    'archivo_hash' => $hash, 'original_hash' => $paquete->documento->archivo_hash,
                    'tamano_bytes' => $archivo->getSize(), 'estado' => 'recibida',
                ]);
                $solicitud->increment('lock_version');
                $this->evento($solicitud, $firma, $actor, ActivityEvent::ApplicationSignatureReceived, 'firma_recibida');

                return $firma;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk($disk)->delete($path);
            }
            throw $exception;
        }
    }

    public function revisar(Solicitud $solicitud, SolicitudFirma $firma, array $data, User $actor): void
    {
        DB::transaction(function () use ($solicitud, $firma, $data, $actor) {
            $paquete = $firma->paquete;
            $solicitud = $this->bloquear($solicitud, $paquete, $actor, 'reviewSignature');
            $firma = SolicitudFirma::lockForUpdate()->findOrFail($firma->id);
            $motivo = trim($data['motivo'] ?? '');
            if ($firma->estado !== 'recibida') {
                if ($firma->estado === $data['estado_firma'] && $firma->revisada_por === $actor->id && ($firma->motivo ?? '') === $motivo) {
                    return;
                }
                $this->error('Esta firma ya fue revisada. Actualiza la página para consultar el resultado.');
            }
            if ($data['estado_firma'] === 'aceptada') {
                $this->validar($solicitud, $paquete, $actor, 'reviewSignature', $data['lock_version']);
                $this->originalIntacto($paquete);
                $this->verificarArchivo($firma);
                if ($firma->original_hash !== $paquete->documento->archivo_hash) {
                    $this->error('La copia no está ligada al PDF original conservado. Solicita revisión al administrador.');
                }
            } else {
                // Rejection must remain possible when evidence expires; it never formalizes.
                if ($solicitud->estado !== SolicitudEstado::Aprobada || $solicitud->lock_version !== (int) $data['lock_version']
                    || $paquete->solicitud_resolucion_id !== $solicitud->resoluciones()->latest('id')->value('id')) {
                    $this->error('La solicitud cambió o el contrato es histórico. Actualiza la página.');
                }
            }
            $firma->update(['estado' => $data['estado_firma'], 'revisada_por' => $actor->id, 'revisada_en' => now(), 'motivo' => $motivo ?: null]);
            if ($firma->estado === 'aceptada') {
                $snapshot = ['modo' => 'qa', 'paquete_id' => $paquete->id, 'paquete_hash' => $paquete->snapshot_hash,
                    'firma_id' => $firma->id, 'firma_hash' => $firma->archivo_hash, 'original_hash' => $firma->original_hash,
                    'fecha_firma' => $firma->fecha_firma->toDateString(), 'revision_id' => $paquete->solicitud_revision_id,
                    'resolucion_id' => $paquete->solicitud_resolucion_id];
                SolicitudFormalizacion::create(['solicitud_paquete_id' => $paquete->id, 'solicitud_firma_id' => $firma->id,
                    'creada_por' => $actor->id, 'modo' => 'qa', 'snapshot' => $snapshot,
                    'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR))]);
                $solicitud->estado = SolicitudEstado::Formalizada;
            }
            $solicitud->lock_version++;
            $solicitud->save();
            $this->evento($solicitud, $firma, $actor, ActivityEvent::ApplicationSignatureReviewed,
                $firma->estado === 'aceptada' ? 'formalizada_qa' : 'firma_rechazada');
        });
    }

    public function verificarArchivo(SolicitudFirma $firma): void
    {
        if ($this->hashArchivo($firma->disk, $firma->path) !== $firma->archivo_hash) {
            $this->error('La copia firmada falta o no coincide con su huella. Solicita recuperar el archivo desde respaldo.');
        }
    }

    private function originalIntacto(SolicitudPaquete $paquete): void
    {
        $doc = $paquete->documento;
        if (! hash_equals($paquete->snapshot_hash, hash('sha256', json_encode($paquete->snapshot, JSON_THROW_ON_ERROR)))
            || $this->hashArchivo($doc->disk, $doc->path) !== $doc->archivo_hash) {
            $this->error('El contrato original falta o no coincide con su huella. Solicita recuperar el respaldo; no lo regeneres.');
        }
    }

    private function hashArchivo(?string $disk, ?string $path): ?string
    {
        try {
            if (! $disk || ! $path || ! Storage::disk($disk)->exists($path)) {
                return null;
            }
            $stream = Storage::disk($disk)->readStream($path);
            if (! is_resource($stream)) {
                return null;
            }
            try {
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);

                return hash_final($hash);
            } finally {
                fclose($stream);
            }
        } catch (\Throwable) {
            return null;
        }
    }

    private function bloquear(Solicitud $solicitud, SolicitudPaquete $paquete, User $actor, string $ability): Solicitud
    {
        $solicitud = Solicitud::lockForUpdate()->findOrFail($solicitud->id);
        Gate::forUser($actor)->authorize($ability, $solicitud);
        abort_unless($paquete->solicitud_id === $solicitud->id, 404);
        if (config('originacion.firmas_qa_habilitadas') !== true || ! $actor->currentSucursal?->activa
            || (int) $actor->current_sucursal_id !== (int) $solicitud->sucursal_id) {
            $this->error('Habilita ORIGINACION_FIRMAS_QA_HABILITADAS en QA y selecciona la sucursal activa responsable.');
        }
        Cliente::lockForUpdate()->findOrFail($solicitud->cliente_id);
        ProductoVersion::lockForUpdate()->findOrFail($solicitud->producto_version_id);

        return $solicitud;
    }

    private function validar(Solicitud $solicitud, SolicitudPaquete $paquete, User $actor, string $ability, int $version): void
    {
        if ($solicitud->lock_version !== $version) {
            $this->error('La solicitud cambió. Actualiza la página antes de continuar.');
        }
        $requisitos = $this->requisitos($solicitud, $paquete, $actor, $ability);
        if (! $requisitos['permitido']) {
            $this->error(collect($requisitos['requisitos'])->where('cumplido', false)->pluck('mensaje')->implode(' '));
        }
    }

    private function evento(Solicitud $solicitud, SolicitudFirma $firma, User $actor, ActivityEvent $event, string $tipo): void
    {
        $solicitud->eventos()->create(['actor_id' => $actor->id, 'tipo' => $tipo, 'datos' => ['firma_id' => $firma->id, 'paquete_id' => $firma->solicitud_paquete_id]]);
        app(ActivityLogService::class)->log($event, $event->label(), $solicitud,
            ['related' => ['type' => 'solicitud_firma', 'id' => $firma->id]], $actor, sucursalId: $solicitud->sucursal_id);
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['firma' => $message]);
    }
}
