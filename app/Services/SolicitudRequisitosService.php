<?php

namespace App\Services;

use App\Enums\ClienteDocumentoEstado;
use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SolicitudRequisitosService
{
    public function __construct(private readonly FechaEmpresa $fecha, private readonly SolicitudExpedienteService $expediente) {}

    /** Call inside the application/client/product transaction when used for approval. */
    public function evaluar(Solicitud $solicitud, User $actor): array
    {
        $items = [];
        $add = function (string $clave, bool $cumplido, string $mensaje) use (&$items): void {
            $items[] = compact('clave', 'cumplido', 'mensaje');
        };
        $revision = $solicitud->revisiones()->latest('numero')->first();
        $politica = $revision?->snapshot['politica_originacion'] ?? null;
        $add('habilitacion', config('originacion.aprobaciones_habilitadas') === true, 'La aprobación está desactivada para toda la instalación. No se resuelve modificando documentos o dictámenes. Solicita al administrador técnico revisar ORIGINACION_APROBACIONES_HABILITADAS y reconstruir la caché de configuración. En QA puede habilitarla para pruebas; en operación real requiere validación del operador.');
        $add('estado', $solicitud->estado === SolicitudEstado::EnRevision && $revision !== null, 'La solicitud debe estar en revisión.');
        $add('permiso', Gate::forUser($actor)->allows('approve', $solicitud), 'Se requiere permiso de aprobación y la sucursal responsable seleccionada.');
        $add('politica', $politica !== null, 'Configura la política del producto y envía una nueva revisión.');
        $evidencia = ['revision_id' => $revision?->id, 'revision_hash' => $revision?->snapshot_hash, 'documentos' => []];
        if (! $politica || ! $revision) {
            return ['requisitos' => $items, 'puede_aprobar' => false, 'evidencia' => $evidencia, 'politica' => null];
        }
        $reglas = $politica['condiciones'];
        $vigente = app(OriginacionPoliticaService::class)->vigente($solicitud->producto_version_id);
        $add('politica_vigente', $vigente?->id === $politica['id'], 'La política cambió. Devuelve y reenvía para aplicar la versión vigente.');
        $productoDisponible = ProductoVersion::disponiblesParaOriginacion($this->fecha->hoy())
            ->whereHas('producto', fn ($q) => $q->where('activo', true))->whereKey($solicitud->producto_version_id)->exists();
        $add('producto', $productoDisponible, 'El producto debe estar activo y vigente para originación.');
        $add('monto', Decimal::compare($solicitud->monto, $reglas['monto_maximo']) <= 0, 'El monto excede el límite de aprobación de la política.');
        $add('fecha', $solicitud->fecha_estimada->toDateString() >= $this->fecha->hoy()->toDateString(), 'Actualiza la fecha estimada mediante una nueva revisión.');
        $capturo = $solicitud->eventos()->where('actor_id', $actor->id)
            ->whereIn('tipo', ['creada', 'borrador_actualizado', 'enviada'])->exists();
        $add('separacion', $reglas['modalidad'] === 'individual' || ! $capturo, 'En modalidad dual debe aprobar una persona que no haya capturado ni enviado la solicitud.');
        $add('sic', $reglas['sic'] === 'manual_permitido', 'La política exige SIC integrado válido; el proveedor productivo todavía no está habilitado.');
        $cliente = Cliente::findOrFail($solicitud->cliente_id);
        $identidad = $this->expediente->identidad($cliente);
        $add('persona_fisica', ($identidad['fiscales']['tipo_persona'] ?? null) === 'fisica', 'Esta entrega solo permite aprobar personas físicas con identidad fiscal registrada.');
        $add('cliente', json_encode($identidad, JSON_THROW_ON_ERROR) === json_encode($revision->snapshot['identidad_cliente'] ?? null, JSON_THROW_ON_ERROR),
            'Los datos evaluados del cliente cambiaron. Devuelve y reenvía una nueva revisión.');
        $huella = $this->expediente->huella($cliente);
        foreach (['evaluacion' => 'favorable', 'pld' => 'sin_observaciones'] as $tipo => $resultado) {
            $dictamen = $solicitud->dictamenes()->where('solicitud_revision_id', $revision->id)->where('tipo', $tipo)->latest('id')->first();
            $add($tipo, $dictamen?->resultado === $resultado, $tipo === 'pld' ? 'Cumplimiento debe concluir la revisión aplicable sin bloqueos pendientes.' : 'Falta evaluación favorable de la revisión actual.');
            $equipo = $tipo === 'pld' ? 'Cumplimiento' : 'Evaluación';
            $mensajeVigencia = ! $dictamen
                ? 'Primero registra el dictamen de '.$equipo.' de esta revisión. Todavía no hay una evaluación que comprobar.'
                : (empty($dictamen->contenido['expediente_hash'])
                    ? 'El dictamen anterior no conserva la referencia del expediente evaluado. '.$equipo.' debe revisar la evidencia actual y registrar un nuevo dictamen.'
                    : 'El expediente cambió después del dictamen (datos, domicilio o documentos, incluida su validación). '.$equipo.' debe revisar la evidencia actual y registrar un nuevo dictamen. Recargar la pantalla no lo renueva.');
            $add($tipo.'_vigente', $dictamen !== null && ($dictamen->contenido['expediente_hash'] ?? null) === $huella, $mensajeVigencia);
            $evidencia[$tipo.'_id'] = $dictamen?->id;
        }
        foreach ($reglas['documentos'] as $tipo) {
            $documento = ClienteDocumento::where('cliente_id', $cliente->id)->where('tipo', $tipo)->where('es_actual', true)->latest('version')->latest('id')->first();
            $valido = $documento && $documento->estado === ClienteDocumentoEstado::Validado
                && (! $documento->vence_en || $documento->vence_en->toDateString() >= $this->fecha->hoy()->toDateString())
                && $this->archivoDisponible($documento);
            $add('documento_'.$tipo, (bool) $valido, 'Documento requerido pendiente, vencido o sin archivo: '.str_replace('_', ' ', $tipo).'.');
            if ($valido) {
                $evidencia['documentos'][] = $documento->only(['id', 'tipo', 'version', 'revisado_en', 'vence_en']);
            }
        }
        $evidencia['politica'] = $politica;
        $evidencia['expediente_hash'] = $huella;

        return ['requisitos' => $items, 'puede_aprobar' => ! collect($items)->contains('cumplido', false), 'evidencia' => $evidencia, 'politica' => $politica];
    }

    private function archivoDisponible(ClienteDocumento $documento): bool
    {
        if (! $documento->path || ! $documento->disk) {
            return false;
        }
        try {
            return Storage::disk($documento->disk)->exists($documento->path);
        } catch (\Throwable) {
            // An unavailable evidence store blocks approval without exposing paths.
            return false;
        }
    }
}
