<?php

namespace App\Services;

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DireccionService
{
    public function obtenerDatosPorCodigoPostal(string $codigoPostal)
    {
        $codigosPostales = CodigoPostal::with(['pais', 'divisionAdministrativa.padre.padre'])->where('codigo', 'like', "$codigoPostal%")->get();

        if ($codigosPostales->isEmpty()) {
            throw new NotFoundHttpException('No se encontraron códigos postales que coincidan.');
        }

        return $codigosPostales;
    }
}
