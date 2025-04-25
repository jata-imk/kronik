<?php

namespace App\Services;

use App\Models\CodigoPostal;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CodigoPostalService
{
    public function buscarPorCodigo(string $codigoPostal)
    {
        $codigosPostales = CodigoPostal::with(['pais', 'divisionAdministrativa.padre.padre'])->where('codigo', 'like', "$codigoPostal%")->get();

        if ($codigosPostales->isEmpty()) {
            throw new NotFoundHttpException('No se encontraron códigos postales que coincidan.');
        }

        return $codigosPostales;
    }

    public function sugerencias(string $codigoPostal): Collection
    {
        return CodigoPostal::where('codigo', 'like', "$codigoPostal%")->distinct()->take(10)->get('codigo');
    }
}
