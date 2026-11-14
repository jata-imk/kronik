<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ClienteListadoService
{
    public function pagina(Request $request, array $scope): array
    {
        $consulta = app(ConsultaSucursal::class);
        $clienteService = app(ClienteService::class);
        $filters = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
            'columnas' => 'nullable|string|max:4096|json',
            'orden' => ['nullable', Rule::in(['id', 'primer_nombre', 'nombre_completo', 'email', 'telefono', 'sexo', 'pais_nacimiento.nombre_es', 'datos_fiscales.rfc', 'sucursal.nombre'])],
            'direccion' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = $consulta->aplicar($clienteService->query(), $scope);
        if ($buscar = $filters['buscar'] ?? null) {
            $this->buscar($query, $buscar);
        }
        $columns = json_decode($filters['columnas'] ?? '{}', true);
        if (! is_array($columns)) {
            throw ValidationException::withMessages(['columnas' => 'Selecciona filtros de clientes válidos.']);
        }
        $allowed = ['global', 'id', 'nombre_completo', 'email', 'telefono', 'sexo', 'pais_nacimiento.nombre_es', 'datos_fiscales.rfc', 'sucursal.nombre'];
        foreach ($columns as $field => $value) {
            if (! in_array($field, $allowed, true) || (! is_string($value) && ! is_numeric($value) && $value !== null)) {
                throw ValidationException::withMessages(['columnas' => 'Selecciona filtros de clientes válidos.']);
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (mb_strlen((string) $value) > 100) {
                throw ValidationException::withMessages(['columnas' => 'Cada filtro admite hasta 100 caracteres.']);
            }
            if (in_array($field, ['global', 'nombre_completo'])) {
                $this->buscar($query, (string) $value, $field === 'nombre_completo');
            } elseif (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field);
                $relation = ['pais_nacimiento' => 'paisNacimiento', 'datos_fiscales' => 'datosFiscales', 'sucursal' => 'sucursal'][$relation];
                $query->whereHas($relation, fn ($q) => $q->where($column, 'like', '%'.$value.'%'));
            } else {
                $query->where($field, $field === 'id' ? '=' : 'like', $field === 'id' ? $value : '%'.$value.'%');
            }
        }
        $sort = $filters['orden'] ?? 'id';
        $direction = $filters['direccion'] ?? 'asc';
        if (str_contains($sort, '.')) {
            [$relation, $column] = explode('.', $sort);
            $relation = ['pais_nacimiento' => 'paisNacimiento', 'datos_fiscales' => 'datosFiscales', 'sucursal' => 'sucursal'][$relation];
            $relation = (new Cliente)->{$relation}();
            $subquery = $relation->getRelated()->newQuery()->select($column);
            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $subquery->whereColumn($relation->getQualifiedOwnerKeyName(), $relation->getQualifiedForeignKeyName());
            } else {
                $subquery->whereColumn($relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName());
            }
            $query->orderBy($subquery->limit(1), $direction);
        } else {
            $query->orderBy($sort === 'nombre_completo' ? 'primer_nombre' : $sort, $direction);
        }
        $clientes = $query->orderBy('clientes.id')
            ->paginate($filters['por_pagina'] ?? 25)->withQueryString();

        return [$clientes, $filters];
    }

    private function buscar(\Illuminate\Database\Eloquent\Builder $query, string $text, bool $soloNombre = false): void
    {
        foreach (preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where(function ($q) use ($word, $soloNombre) {
                foreach (['primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno', ...($soloNombre ? [] : ['email', 'telefono'])] as $field) {
                    $q->orWhere($field, 'like', '%'.$word.'%');
                }
                if (! $soloNombre) {
                    $q->orWhere('clientes.id', ctype_digit($word) ? (int) $word : 0)
                        ->orWhereHas('sucursal', fn ($q) => $q->where('nombre', 'like', '%'.$word.'%'))
                        ->orWhereHas('datosFiscales', fn ($q) => $q->where('rfc', 'like', '%'.$word.'%'))
                        ->orWhereHas('paisNacimiento', fn ($q) => $q->where('nombre_es', 'like', '%'.$word.'%'));
                }
            });
        }
    }
}
