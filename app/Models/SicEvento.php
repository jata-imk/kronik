<?php

namespace App\Models;

use App\Models\Concerns\ConservaEvidenciaSic;
use Illuminate\Database\Eloquent\Model;

class SicEvento extends Model
{
    use ConservaEvidenciaSic;

    protected $guarded = [];
}
