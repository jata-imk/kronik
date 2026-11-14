<?php

namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Query scope only: callers must authorize their domain before resolving it. */
final class ConsultaSucursal
{
    public function resolver(Request $request, string $default = 'current'): array
    {
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(['current', 'all'])],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
        ], [], ['scope' => 'alcance de consulta', 'sucursal_id' => 'sucursal']);
        $scope = $filters['scope'] ?? ($request->filled('sucursal_id') ? 'all' : $default);
        $active = $request->user()->currentSucursal;

        return [
            'scope' => $scope,
            'sucursal_id' => $scope === 'current' ? ($active?->activa ? $active->id : null) : ($filters['sucursal_id'] ?? null),
            'requiereSucursal' => $scope === 'current' && ! $active?->activa,
        ];
    }

    public function aplicar(Builder $query, array $scope, string $column = 'sucursal_id'): Builder
    {
        if ($scope['requiereSucursal']) {
            return $query->whereRaw('1 = 0');
        }

        return $query->when($scope['sucursal_id'], fn ($query, $id) => $query->where($column, $id));
    }

    public function opciones(): mixed
    {
        // Institutional read is authorized by each caller, not by branch membership.
        return Sucursal::orderBy('nombre')->get(['id', 'nombre', 'activa']);
    }
}
