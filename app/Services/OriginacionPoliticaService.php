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

    public function crear(ProductoVersion $producto, array $data, User $actor): OriginacionPolitica
    {
        Gate::forUser($actor)->authorize('manage origination productos-crediticios');
        Gate::forUser($actor)->authorize('read productos-crediticios');
        $data = Validator::make($data, [
            'version_anterior' => 'required|integer|min:0',
            'modalidad' => ['required', Rule::in(['individual', 'dual'])],
            'sic' => ['required', Rule::in(['requerido', 'manual_permitido'])],
            'monto_maximo' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'vigencia_dias' => 'required|integer|min:1|max:365',
            'documentos' => 'present|array|min:1|max:5',
            'documentos.*' => ['required', 'distinct', Rule::enum(ClienteDocumentoTipo::class)],
            'referencia_validacion' => 'required|string|min:20|max:1000',
            'criterio_capacidad' => 'required|string|min:20|max:1000',
            'confirmacion' => 'accepted',
        ])->validate();

        return DB::transaction(function () use ($producto, $data, $actor) {
            $producto = ProductoVersion::query()->lockForUpdate()->findOrFail($producto->id);
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
