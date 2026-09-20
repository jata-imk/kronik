<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SolicitudEvento extends Model
{
    protected $guarded = [];

    protected $casts = ['datos' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['solicitud' => 'Los eventos de solicitud no pueden alterarse ni eliminarse.']);
        static::updating($reject);
        static::deleting($reject);
    }
}
