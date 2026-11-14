<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'cancelled_at' => 'immutable_datetime',
    ];
}
