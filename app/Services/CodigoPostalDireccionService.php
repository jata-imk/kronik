<?php

namespace App\Services;

use App\Models\CodigoPostal;
use Illuminate\Support\Str;

class CodigoPostalDireccionService
{
    public function canonicalize(array $direccion, bool $includeTextFields = true): array
    {
        $codigo = preg_replace('/\D/', '', (string) ($direccion['codigo_postal'] ?? ''));

        if (strlen($codigo) !== 5) {
            return $direccion;
        }

        $candidatos = CodigoPostal::query()
            ->with(['pais', 'divisionAdministrativa.padre.padre'])
            ->where('codigo', $codigo)
            ->get();

        $codigoPostal = $this->selectCandidate($candidatos, $direccion);

        if (! $codigoPostal) {
            return $direccion;
        }

        $nivelTres = $codigoPostal->divisionAdministrativa;
        $nivelDos = $nivelTres?->padre;
        $nivelUno = $nivelDos?->padre;

        $direccion = array_merge($direccion, [
            'pais_id' => $codigoPostal->pais_id,
            'codigo_postal' => $codigoPostal->codigo,
            'codigo_postal_id' => $codigoPostal->id,
            'division_admin_uno_id' => $nivelUno?->id,
            'division_admin_dos_id' => $nivelDos?->id,
            'division_admin_tres_id' => $nivelTres?->id,
        ]);

        if ($includeTextFields) {
            $direccion = array_merge($direccion, [
                'pais_codigo_iso' => $codigoPostal->pais?->codigo_iso,
                'colonia' => $nivelTres?->nombre,
                'municipio' => $nivelDos?->nombre,
                'estado' => $nivelUno?->nombre,
                'pais' => $codigoPostal->pais?->nombre_es,
            ]);
        }

        return $direccion;
    }

    private function selectCandidate($candidatos, array $direccion): ?CodigoPostal
    {
        if ($candidatos->isEmpty()) {
            return null;
        }

        $codigoPostalId = $direccion['codigo_postal_id'] ?? null;
        if (filled($codigoPostalId)) {
            $porId = $candidatos->firstWhere('id', (int) $codigoPostalId);
            if ($porId) {
                return $porId;
            }
        }

        $divisionId = $direccion['division_admin_tres_id'] ?? null;
        if (filled($divisionId)) {
            $porDivision = $candidatos->firstWhere('division_admin_id', (int) $divisionId);
            if ($porDivision) {
                return $porDivision;
            }
        }

        $colonia = $this->normalizeName($direccion['colonia'] ?? null);
        if ($colonia !== '') {
            $porColonia = $candidatos->filter(
                fn (CodigoPostal $item) => $this->normalizeName($item->divisionAdministrativa?->nombre) === $colonia,
            );

            if ($porColonia->count() === 1) {
                return $porColonia->first();
            }
        }

        return $candidatos->count() === 1 ? $candidatos->first() : null;
    }

    private function normalizeName(?string $value): string
    {
        return Str::of($value ?? '')
            ->ascii()
            ->lower()
            ->squish()
            ->toString();
    }
}
