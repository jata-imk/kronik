<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationRule extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean', 'daily_limit' => 'integer', 'template_version' => 'integer'];
}
