<?php

namespace App\Services;

use App\Models\Pais;

class PaisService
{
    public function readAll($columns = ['*'])
    {
        return Pais::select($columns)->get();
    }
}
