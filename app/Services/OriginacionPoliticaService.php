<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ClienteDocumentoTipo;
use App\Models\OriginacionPolitica;
use App\Models\ProductoVersion;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OriginacionPoliticaService
{
    public function vigente(int $productoVersionId): ?OriginacionPolitica
    {
        return OriginacionPolitica::where('producto_version_id', $productoVersionId)->latest('numero')->first();
    }

    public static function reglas(): array
    {
        return [
            'version_anterior' => 'required|integer|min:0',
            'modalidad' => ['required', Rule::in(['individual', 'dual'])],
            'sic' => ['required', Rule::in(['requerido', 'manual_permitido'])],
            'sic_apis' => ['exclude_unless:sic,requerido', 'required', 'array', 'min:1', 'max:3'],
            'sic_apis.*' => ['required', 'distinct', Rule::in(array_keys(config('sic.servicios')))],
            'sic_sin_score' => ['exclude_unless:sic,requerido', 'required', Rule::in(['bloquear', 'evaluacion_manual'])],
            'monto_maximo' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'vigencia_dias' => 'required|integer|min:1|max:365',
            'documentos' => 'present|array|min:1|max:5',
            'documentos.*' => ['required', 'distinct', Rule::enum(ClienteDocumentoTipo::class)],
            'referencia_validacion' => 'required|string|min:20|max:1000',
            'criterio_capacidad' => 'required|string|min:20|max:1000',
            'confirmacion' => 'accepted',
        ];
    }

    public function resumen(ProductoVersion $version, User $actor, ?string $nombre = null): array
    {
        $politica = $version->relationLoaded('politicaActual') ? $version->politicaActual : $this->vigente($version->id);
        $nombre ??= $version->producto->nombre;
        $lee = Gate::forUser($actor)->allows('read productos-crediticios');
        $gestiona = $lee && Gate::forUser($actor)->allows('manage origination productos-crediticios') && $version->estado !== \App\Enums\ProductoVersionEstado::Retirada;

        return [
            'existe' => $politica !== null, 'numero' => $politica?->numero,
            'mensaje' => $politica ? "Política actual {$politica->numero}." : 'Sin política de originación: se permite capturar y enviar, pero no aprobar solicitudes.',
            'ubicacion' => "Productos crediticios › {$nombre} › Versión de producto {$version->numero} › Política de originación",
            'siguiente' => $gestiona ? 'Configura la política y envía una nueva revisión de la solicitud cuando corresponda.' : 'Solicita al administrador de productos la configuración o revisión de la política.',
            'accion' => $lee ? ['ruta' => 'originacion-politicas.show', 'parametros' => ['version' => $version->id, 'modo' => $gestiona && ! $politica ? 'nueva' : 'consulta'], 'etiqueta' => $gestiona && ! $politica ? 'Configurar política' : 'Consultar política'] : null,
        ];
    }

    public function crear(ProductoVersion $producto, array $data, User $actor): OriginacionPolitica
    {
        Gate::forUser($actor)->authorize('manage origination productos-crediticios');
        Gate::forUser($actor)->authorize('read productos-crediticios');
        $data = Validator::make($data, self::reglas())->validate();

        return DB::transaction(function () use ($producto, $data, $actor) {
            $producto = ProductoVersion::query()->lockForUpdate()->findOrFail($producto->id);
            if ($producto->estado === \App\Enums\ProductoVersionEstado::Retirada) {
                throw ValidationException::withMessages(['politica' => 'No se pueden crear políticas en versiones retiradas. Su historia sigue disponible para consulta.']);
            }
            if (($this->vigente($producto->id)?->numero ?? 0) !== (int) $data['version_anterior']) {
                throw ValidationException::withMessages(['politica' => 'La política cambió. Actualiza la página antes de guardar.']);
            }
            if (Decimal::compare((string) $data['monto_maximo'], $producto->monto_maximo) > 0
                || Decimal::compare((string) $data['monto_maximo'], $producto->monto_minimo) < 0) {
                throw ValidationException::withMessages(['monto_maximo' => 'El límite debe estar dentro del rango de montos del producto.']);
            }
            unset($data['version_anterior'], $data['confirmacion']);
            $data['monto_maximo'] = Decimal::round((string) $data['monto_maximo']);
            $data['vigencia_dias'] = (int) $data['vigencia_dias'];
            sort($data['documentos']);
            $politica = OriginacionPolitica::create([
                'producto_version_id' => $producto->id,
                'numero' => ($this->vigente($producto->id)?->numero ?? 0) + 1,
                'creada_por' => $actor->id, 'condiciones' => $data,
                'snapshot_hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
            ]);
            app(ActivityLogService::class)->log(ActivityEvent::OriginationPolicyCreated,
                'Política de originación versionada', $producto->producto,
                ['related' => ['type' => 'originacion_politica', 'id' => $politica->id]], $actor);

            return $politica;
        });
    }
}
