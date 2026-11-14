<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorNotification extends Model
{
    protected $fillable = [
        'user_id', 'team_id', 'sucursal_id', 'event', 'priority', 'title', 'body', 'dedup_key',
    ];

    protected $hidden = ['dedup_key'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
