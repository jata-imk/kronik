<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class NotificationTemplateVersion extends Model
{
    protected $guarded = [];

    protected $casts = ['activated_at' => 'datetime', 'retired_at' => 'datetime', 'number' => 'integer'];

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function deliveries()
    {
        return $this->hasMany(CommunicationDelivery::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $version) {
            $state = static::whereKey($version->id)->value('state');
            if ($version->isDirty(['notification_template_id', 'number', 'created_by'])
                || (($state !== 'draft' || $version->deliveries()->exists())
                    && $version->isDirty(['format', 'subject', 'body', 'content_hash', 'change_summary']))) {
                throw ValidationException::withMessages(['version' => 'Una versión activa, retirada o utilizada es inmutable. Cree una nueva versión.']);
            }
            if ($version->isDirty('state') && (($state === 'active' && $version->state !== 'retired') || $state === 'retired')) {
                throw ValidationException::withMessages(['version' => 'Una versión retirada no puede reactivarse. Cree una nueva versión.']);
            }
        });
        static::deleting(fn () => throw ValidationException::withMessages(['version' => 'Las versiones conservan su historial y no pueden eliminarse.']));
    }
}
