<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationDelivery extends Model
{
    protected $guarded = [];

    protected $hidden = ['recipient_hash', 'message_snapshot'];

    protected $casts = ['message_snapshot' => 'encrypted:array', 'queued_at' => 'datetime', 'attempted_at' => 'datetime', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];

    public function notificationVersion()
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'notification_template_version_id');
    }

    public function event()
    {
        return $this->belongsTo(CommunicationEvent::class, 'communication_event_id');
    }
}
