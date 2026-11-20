<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationConsent extends Model
{
    protected $guarded = [];

    protected $hidden = ['contact_hash', 'evidence_reference'];

    protected $casts = ['granted_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
