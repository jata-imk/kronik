<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\DocumentoGeneradoEstado;
use App\Enums\DocumentoPlantillaTipo;
use App\Enums\DocumentoPlantillaVersionEstado;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Jobs\GenerarDocumentoPdf;
use App\Models\Cliente;
use App\Models\DocumentoGenerado;
use App\Models\DocumentoPlantillaVersion;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudPaquete;
use App\Models\User;
use App\Services\Credito\SimuladorCreditoSimple;
use App\Services\Documentos\CompiladorPlantillaDocumento;
use App\Services\Documentos\ResolverVariablesDocumento;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SolicitudPaqueteService
{
    public function requisitos(Solicitud $solicitud, User $actor): array
    {
        $result = app(SolicitudRequisitosService::class)->evaluar($solicitud, $actor, paraPaquete: true);
        $items = $result['requisitos'];
        $items[] = ['clave' => 'qa', 'cumplido' => config('originacion.paquetes_qa_habilitados') === true,
            'mensaje' => 'El administrador técnico debe habilitar ORIGINACION_PAQUETES_QA_HABILITADOS y reconstruir la caché de configuración en QA. Esta entrega no genera contratos válidos para operación real.'];
        $items[] = ['clave' => 'sucursal', 'cumplido' => $actor->current_sucursal_id !== null
            && (int) $actor->current_sucursal_id === (int) $solicitud->sucursal_id && (bool) $actor->currentSucursal?->activa,
            'mensaje' => 'Selecciona la sucursal activa responsable de la solicitud antes de preparar o reintentar el paquete.'];

        // Historical packages are retried from their snapshot, never recalculated.
        $resolucionId = $solicitud->resoluciones()->latest('id')->value('id');
        if (! SolicitudPaquete::where('solicitud_resolucion_id', $resolucionId)->exists()
            && ! collect($items)->contains('cumplido', false)) {
            try {
                $this->tablaFiscal($solicitud);
                $items[] = ['clave' => 'fiscalidad', 'cumplido' => true, 'mensaje' => 'Proyección fiscal disponible para el nuevo paquete QA.'];
            } catch (ValidationException $exception) {
                $items[] = ['clave' => 'fiscalidad', 'cumplido' => false,
                    'mensaje' => collect($exception->errors())->flatten()->implode(' ').(isset($exception->errors()['fiscalidad'])
                        ? ' Solicita al administrador de productos una nueva versión con fiscalidad definida y devuelve la solicitud para seleccionarla y aprobarla de nuevo.'
                        : ' Revisa las condiciones antes de preparar el paquete; no se han creado documentos.')];
            }
        }

        return ['requisitos' => $items, 'puede_preparar' => ! collect($items)->contains('cumplido', false)];
    }

    public function preparar(Solicitud $solicitud, array $data, User $actor): SolicitudPaquete
    {
        return DB::transaction(function () use ($solicitud, $data, $actor) {
            $solicitud = Solicitud::lockForUpdate()->findOrFail($solicitud->id);
            Gate::forUser($actor)->authorize('preparePackage', $solicitud);
            $cliente = Cliente::lockForUpdate()->findOrFail($solicitud->cliente_id);
            if ($solicitud->producto_version_id) {
                ProductoVersion::lockForUpdate()->findOrFail($solicitud->producto_version_id);
            }
            $this->validarRequisitos($solicitud, $actor);
            $resolucion = $solicitud->resoluciones()->latest('id')->firstOrFail();
            $existing = SolicitudPaquete::where('solicitud_resolucion_id', $resolucion->id)->first();
            if ($existing) {
                if ($existing->documento_plantilla_version_id !== (int) $data['version_id']) {
                    $this->error('version_id', 'Esta aprobación ya tiene un paquete. Para cambiar la plantilla, devuelve y reenvía la solicitud.');
                }

                return $existing;
            }
            if ($solicitud->lock_version !== (int) $data['lock_version']) {
                $this->error('paquete', 'La solicitud cambió. Actualiza la página antes de preparar el paquete.');
            }
            if (DocumentoGenerado::where('idempotency_key', $data['idempotency_key'])->exists()) {
                $this->error('idempotency_key', 'Este identificador ya pertenece a otra generación. Actualiza la página.');
            }
            $version = DocumentoPlantillaVersion::with('plantilla')->lockForUpdate()->findOrFail($data['version_id']);
            if ($version->estado !== DocumentoPlantillaVersionEstado::Activa || ! $version->plantilla->activa || $version->plantilla->tipo !== DocumentoPlantillaTipo::Contrato) {
                $this->error('version_id', 'Selecciona una versión activa de una plantilla de contrato.');
            }
            $revision = $solicitud->revisiones()->latest('numero')->firstOrFail();
            $compiler = app(CompiladorPlantillaDocumento::class);
            $resolved = app(ResolverVariablesDocumento::class)->resolve(
                $compiler->variables($version->encabezado_html ?? '', $version->contenido_html, $version->pie_html ?? ''), $cliente);
            $snapshot = [
                'formato' => 2, 'modo' => 'qa', 'fiscalidad' => 'proyeccion', 'solicitud_id' => $solicitud->id,
                'revision_id' => $revision->id, 'revision_hash' => $revision->snapshot_hash,
                'condiciones' => $revision->snapshot['condiciones'], 'producto' => $revision->snapshot['producto'],
                'tabla' => $this->tablaFiscal($solicitud),
                'plantilla' => ['nombre' => $version->plantilla->nombre, 'numero' => $version->numero,
                    ...$version->only(['encabezado_html', 'contenido_html', 'pie_html', 'contenido_hash', 'presentacion'])],
                'variables' => $resolved['values'],
            ];
            $paquete = SolicitudPaquete::create([
                'solicitud_id' => $solicitud->id, 'solicitud_revision_id' => $revision->id,
                'solicitud_resolucion_id' => $resolucion->id, 'documento_plantilla_version_id' => $version->id,
                'creado_por' => $actor->id, 'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            ]);
            $documento = $paquete->documento()->create([
                'documento_plantilla_version_id' => $version->id, 'cliente_id' => $cliente->id,
                'estado' => DocumentoGeneradoEstado::Pendiente, 'idempotency_key' => $data['idempotency_key'],
                'datos_utilizados' => $resolved['values'], 'metadatos_variables' => $resolved['metadata'],
                'solicitado_en' => now(), 'creado_por' => $actor->id,
            ]);
            $solicitud->increment('lock_version');
            $solicitud->eventos()->create(['actor_id' => $actor->id, 'tipo' => 'paquete_preparado', 'datos' => ['paquete_id' => $paquete->id]]);
            $this->registrar($solicitud, $documento, $actor);
            GenerarDocumentoPdf::dispatch($documento->id)->afterCommit();

            return $paquete;
        });
    }

    public function reintentar(Solicitud $solicitud, SolicitudPaquete $paquete, User $actor): void
    {
        DB::transaction(function () use ($solicitud, $paquete, $actor) {
            $solicitud = Solicitud::lockForUpdate()->findOrFail($solicitud->id);
            Gate::forUser($actor)->authorize('preparePackage', $solicitud);
            abort_unless($paquete->solicitud_id === $solicitud->id, 404);
            Cliente::lockForUpdate()->findOrFail($solicitud->cliente_id);
            ProductoVersion::lockForUpdate()->findOrFail($solicitud->producto_version_id);
            $this->validarRequisitos($solicitud, $actor);
            if ($paquete->solicitud_resolucion_id !== $solicitud->resoluciones()->latest('id')->value('id')) {
                $this->error('paquete', 'Este paquete pertenece a una aprobación anterior. Consulta el paquete de la revisión actual.');
            }
            $documento = $paquete->documento()->lockForUpdate()->firstOrFail();
            if ($documento->estado !== DocumentoGeneradoEstado::Fallido || $documento->archivo_hash !== null) {
                $this->error('paquete', 'Solo puedes reintentar una generación fallida que todavía no produjo un PDF.');
            }
            $documento->update(['estado' => DocumentoGeneradoEstado::Pendiente, 'error_codigo' => null, 'error_mensaje' => null]);
            $solicitud->eventos()->create(['actor_id' => $actor->id, 'tipo' => 'paquete_reintentado', 'datos' => ['paquete_id' => $paquete->id]]);
            $this->registrar($solicitud, $documento, $actor);
            GenerarDocumentoPdf::dispatch($documento->id)->afterCommit();
        });
    }

    private function validarRequisitos(Solicitud $solicitud, User $actor): void
    {
        $estado = $this->requisitos($solicitud, $actor);
        if (! $estado['puede_preparar']) {
            $this->error('paquete', collect($estado['requisitos'])->where('cumplido', false)->pluck('mensaje')->implode(' '));
        }
    }

    private function tablaFiscal(Solicitud $solicitud): array
    {
        $revision = $solicitud->revisiones()->latest('numero')->firstOrFail();
        $snapshot = $revision->snapshot;
        $producto = ProductoVersion::findOrFail($revision->producto_version_id);
        if (! hash_equals($revision->snapshot_hash, hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)))
            || $producto->snapshot !== $snapshot['producto']) {
            $this->error('paquete', 'La revisión o la versión del producto no coincide con la evidencia conservada. Solicita una revisión al administrador.');
        }
        $condiciones = $snapshot['condiciones'];

        return app(SimuladorCreditoSimple::class)->simular($producto, (string) $condiciones['monto'],
            PeriodicidadCredito::from($condiciones['periodicidad']), (int) $condiciones['plazo'],
            MetodoAmortizacion::from($condiciones['metodo']),
            CarbonImmutable::parse($snapshot['simulacion_informativa']['tabla'][0]['fecha'], app(FechaEmpresa::class)->zonaHoraria()),
            incluirImpuestos: true);
    }

    private function registrar(Solicitud $solicitud, DocumentoGenerado $documento, User $actor): void
    {
        app(ActivityLogService::class)->log(ActivityEvent::DocumentGenerationRequested, 'Paquete QA solicitado', $solicitud,
            ['related' => ['type' => 'documento_generado', 'id' => $documento->id]], $actor, sucursalId: $solicitud->sucursal_id);
    }

    private function error(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
