<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationDelivery extends Model
{
    protected $guarded = [];

    protected $hidden = ['recipient_hash'];

    protected $casts = ['queued_at' => 'datetime', 'attempted_at' => 'datetime', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];

    public function event()
    {
        return $this->belongsTo(CommunicationEvent::class, 'communication_event_id');
    }
}
