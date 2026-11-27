<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Models\CommunicationDelivery;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Services\ActivityLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NotificationTemplateService
{
    public function __construct(private NotificationContent $content, private ActivityLogService $activity) {}

    public function create(array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = NotificationTemplate::create(Arr::only($data, ['key', 'name', 'description', 'event_type', 'channel']));
            $version = $template->versions()->create([...$this->content->content($template->channel, $data),
                'number' => 1, 'state' => 'draft', 'created_by' => auth()->id(), 'change_summary' => $data['change_summary'] ?? null]);
            $this->audit(ActivityEvent::NotificationTemplateCreated, $version);

            return $template;
        });
    }

    public function update(NotificationTemplate $template, NotificationTemplateVersion $version, array $data): void
    {
        DB::transaction(function () use ($template, $version, $data) {
            [$template, $version] = $this->locked($template, $version);
            $this->editable($version);
            $template->fill(Arr::only($data, ['name', 'description']));
            $version->fill([...$this->content->content($template->channel, $data), 'change_summary' => $data['change_summary'] ?? null]);
            $changed = $template->isDirty() || $version->isDirty();
            $template->save();
            $version->save();
            if ($changed) {
                $this->audit(ActivityEvent::NotificationTemplateUpdated, $version);
            }
        });
    }

    public function duplicate(NotificationTemplate $template, NotificationTemplateVersion $source): NotificationTemplateVersion
    {
        return DB::transaction(function () use ($template, $source) {
            [$template, $source] = $this->locked($template, $source);
            $version = $template->versions()->create([...$source->only(['format', 'subject', 'body', 'content_hash']),
                'number' => $template->versions()->max('number') + 1, 'state' => 'draft',
                'created_by' => auth()->id(), 'change_summary' => "Duplicada desde la versión {$source->number}"]);
            $this->audit(ActivityEvent::NotificationTemplateVersioned, $version);

            return $version;
        });
    }

    public function activate(NotificationTemplateVersion $version): void
    {
        DB::transaction(function () use ($version) {
            [$template, $version] = $this->locked($version->template, $version);
            if ($version->state === 'active') {
                return;
            }
            $this->editable($version);
            $this->content->content($template->channel, $version->toArray());
            foreach ($template->versions()->where('state', 'active')->lockForUpdate()->get() as $previous) {
                $this->retireLocked($previous);
            }
            $version->update(['state' => 'active', 'activated_at' => now()]);
            $this->audit(ActivityEvent::NotificationTemplateActivated, $version);
        });
    }

    public function retire(NotificationTemplateVersion $version): void
    {
        DB::transaction(function () use ($version) {
            [, $version] = $this->locked($version->template, $version);
            if ($version->state === 'retired') {
                return;
            }
            if ($version->state !== 'active') {
                throw ValidationException::withMessages(['version' => 'Solo puede retirarse una versión activa.']);
            }
            $this->retireLocked($version);
        });
    }

    private function retireLocked(NotificationTemplateVersion $version): void
    {
        $version->update(['state' => 'retired', 'retired_at' => now()]);
        $this->cancelPending(CommunicationDelivery::where('notification_template_version_id', $version->id));
        $this->audit(ActivityEvent::NotificationTemplateRetired, $version);
    }

    public function cancelPending($query): void
    {
        foreach ($query->whereIn('status', ['prepared', 'queued'])->whereNull('attempted_at')->orderBy('id')->lockForUpdate()->get() as $delivery) {
            $delivery->update(['status' => 'cancelled', 'reason' => 'notification_template_changed']);
            $this->activity->log(ActivityEvent::CommunicationDeliveryCancelled, 'Salida cancelada por cambio de plantilla', $delivery,
                ['state' => 'cancelled'], causer: auth()->user(), teamId: $delivery->team_id, sucursalId: $delivery->event?->sucursal_id, inheritActorContext: false);
        }
    }

    private function locked(NotificationTemplate $template, NotificationTemplateVersion $version): array
    {
        $template = NotificationTemplate::lockForUpdate()->findOrFail($template->id);
        $version = $template->versions()->lockForUpdate()->findOrFail($version->id);

        return [$template, $version];
    }

    private function editable(NotificationTemplateVersion $version): void
    {
        if ($version->state !== 'draft' || $version->deliveries()->exists()) {
            throw ValidationException::withMessages(['version' => 'Esta versión es inmutable. Cree una nueva versión.']);
        }
    }

    private function audit(ActivityEvent $event, NotificationTemplateVersion $version): void
    {
        $actor = auth()->user();
        $this->activity->log($event, $event->label(), $version->template,
            ['related' => ['type' => 'notification_template_version', 'id' => $version->id], 'state' => $version->state, 'result' => 'applied'],
            $actor, $actor?->current_team_id, $actor?->current_sucursal_id, inheritActorContext: false);
    }
}
