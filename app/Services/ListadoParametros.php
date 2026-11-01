<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Shared list parameters only. Authorization and column mappings belong to the domain. */
final class ListadoParametros
{
    public function resolver(Request $request, array $filters, array $sorts, array $defaults = []): array
    {
        $values = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'columnas' => ['nullable', 'string', 'max:4096', 'json'],
            'orden' => ['nullable', Rule::in(array_keys($sorts))],
            'direccion' => ['nullable', Rule::in(['asc', 'desc'])],
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [], ['buscar' => 'búsqueda', 'columnas' => 'filtros', 'orden' => 'orden', 'direccion' => 'dirección', 'por_pagina' => 'registros por página', 'page' => 'página']);
        $columns = array_key_exists('columnas', $values) ? json_decode($values['columnas'] ?? '{}', true) : $defaults;
        if (! is_array($columns)) {
            throw ValidationException::withMessages(['columnas' => 'Selecciona filtros válidos.']);
        }
        foreach ($columns as $key => $value) {
            if (! array_key_exists($key, $filters) || (! is_scalar($value) && $value !== null) || mb_strlen((string) $value) > 100) {
                throw ValidationException::withMessages(['columnas' => 'Selecciona filtros válidos de hasta 100 caracteres.']);
            }
        }
        if (filled($values['buscar'] ?? null)) {
            $columns['global'] = $values['buscar'];
        }

        return [...$values, 'columnas' => $columns, 'por_pagina' => (int) ($values['por_pagina'] ?? 25)];
    }

    public function aplicar(Builder $query, array $values, array $filters, array $sorts, string $default): Builder
    {
        foreach ($values['columnas'] as $field => $value) {
            if ($value !== null && $value !== '') {
                $filters[$field]($query, (string) $value);
            }
        }
        $sort = $sorts[$values['orden'] ?? $default];
        $query->orderBy($sort, $values['direccion'] ?? 'asc');

        return $query->orderBy($query->getModel()->qualifyColumn('id'));
    }

    public function metadata($paginator, array $values): array
    {
        return ['total' => $paginator->total(), 'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(), 'filters' => $values];
    }
}
