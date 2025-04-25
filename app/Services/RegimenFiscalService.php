<?php

namespace App\Services;

use App\Models\RegimenFiscal;

class RegimenFiscalService
{
    public function readAll($columns = ['*'])
    {
        return RegimenFiscal::select($columns)->get();
    }
}
