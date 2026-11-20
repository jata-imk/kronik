<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationChannel extends Model
{
    protected $guarded = [];

    protected $hidden = ['secret'];

    protected $casts = ['secret' => 'encrypted', 'enabled' => 'boolean', 'checked_at' => 'datetime'];
}
