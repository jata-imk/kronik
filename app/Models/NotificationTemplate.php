<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class NotificationTemplate extends Model
{
    protected $guarded = [];

    public function versions()
    {
        return $this->hasMany(NotificationTemplateVersion::class);
    }

    public function activeVersion()
    {
        return $this->hasOne(NotificationTemplateVersion::class)->ofMany(['number' => 'max'], fn ($q) => $q->where('state', 'active'));
    }

    protected static function booted(): void
    {
        static::updating(function (self $template) {
            if ($template->isDirty(['key', 'channel', 'event_type'])) {
                throw ValidationException::withMessages(['template' => 'La clave, el canal y el evento de una plantilla no pueden cambiarse.']);
            }
        });
    }
}
