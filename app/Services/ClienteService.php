<?php

namespace App\Services;

use App\Models\Cliente;

class ClienteService
{
    public function readAll()
    {
        return Cliente::with([
            'paisNacimiento',
            'datosFiscales.regimenFiscal',
            'direcciones' => [
                'pais',
                'codigoPostal.divisionAdministrativa.padre.padre',
                'divisionAdministrativaUno',
                'divisionAdministrativaDos',
                'divisionAdministrativaTres'
            ]
        ])->get();
    }
}
