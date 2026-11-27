<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationRule;
use App\Models\NotificationTemplate;
use App\Services\ActivityLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommunicationRuleService
{
    public function save(array $data, \App\Models\User $actor): void
    {
        DB::transaction(function () use ($data, $actor) {
            $lookup = ['team_id' => $actor->current_team_id, 'event_type' => $data['event_type'], 'channel' => $data['channel']];
            $prior = CommunicationRule::where($lookup)->first();
            // All paths acquire template locks before rule/delivery locks.
            $ids = array_filter([$prior?->notification_template_id, $data['notification_template_id'] ?? null]);
            $locked = NotificationTemplate::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $rule = CommunicationRule::where($lookup)->lockForUpdate()->first() ?? new CommunicationRule($lookup);
            $old = $rule->exists ? $rule->only(['subject', 'body', 'external_template_name', 'external_template_locale', 'notification_template_id']) : null;
            if ($data['channel'] !== 'whatsapp') {
                $template = $locked->get($data['notification_template_id'] ?? null);
                if (($template && ($template->channel !== $data['channel'] || $template->event_type !== $data['event_type']))
                    || ($data['enabled'] && (! $template || ! $template->activeVersion()->lockForUpdate()->first()))) {
                    throw ValidationException::withMessages(['notification_template_id' => 'Seleccione una plantilla global compatible con una versión activa.']);
                }
                $data = Arr::except($data, ['body', 'subject', 'external_template_name', 'external_template_locale', 'external_template_status']);
                $data['body'] = '';
                $data['subject'] = null;
            } else {
                foreach ([$data['subject'] ?? '', $data['body']] as $text) {
                    preg_match_all('/\{([^{}]+)\}/u', $text, $matches);
                    if (array_diff($matches[1], NotificationContent::VARIABLES)) {
                        throw ValidationException::withMessages(['body' => 'La plantilla contiene una variable no permitida.']);
                    }
                }
                $data['notification_template_id'] = null;
            }
            if ($data['event_type'] === 'payment_received') {
                $data['lead_days'] = config('comunicaciones.default_lead_days');
            }
            $rule->fill($data);
            $changed = $old && $old !== $rule->only(array_keys($old));
            if ($changed) {
                $rule->template_version++;
            }
            $rule->save();
            if ($changed && $rule->channel !== 'whatsapp') {
                app(NotificationTemplateService::class)->cancelPending(CommunicationDelivery::where('team_id', $rule->team_id)->where('channel', $rule->channel)->whereHas('event', fn ($q) => $q->where('event_type', $rule->event_type)));
            }
            app(ActivityLogService::class)->log(ActivityEvent::CommunicationRuleConfigured, 'Regla de comunicación configurada', $rule,
                ['state' => $rule->enabled ? 'enabled' : 'disabled'], $actor, $actor->current_team_id, $actor->current_sucursal_id, inheritActorContext: false);
        });
    }
}
