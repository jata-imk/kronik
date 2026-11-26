<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Http\Requests\Communications\ConfigureChannelRequest;
use App\Http\Requests\Communications\ConfigureRuleRequest;
use App\Http\Requests\Communications\GrantConsentRequest;
use App\Mail\CommunicationMail;
use App\Models\Cliente;
use App\Models\CommunicationChannel;
use App\Models\CommunicationConsent;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationRule;
use App\Services\ActivityLogService;
use App\Services\Communications\CommunicationChannelService;
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\EmailTransport;
use App\Services\Communications\LabsMobileConfiguration;
use App\Services\Communications\LabsMobileSmsTransport;
use App\Services\Communications\MetaWhatsAppTransport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('read comunicaciones');
        $teamId = $request->user()->current_team_id;
        abort_unless($teamId, 403);
        $filters = $request->validate([
            'channel' => ['nullable', 'in:email,sms,whatsapp'],
            'event_type' => ['nullable', 'in:payment_received,upcoming_due'],
            'status' => ['nullable', 'in:prepared,queued,sending,accepted,delivered,read,failed,cancelled,uncertain'],
            'cliente_id' => ['nullable', 'integer', 'min:1'],
            'credito_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $versionSummary = fn ($query) => $query->select('notification_template_versions.id', 'notification_template_versions.notification_template_id', 'notification_template_versions.number', 'notification_template_versions.state');
        $history = CommunicationDelivery::with('notificationVersion:id,notification_template_id,number,state')->where('team_id', $teamId)
            ->whereHas('event', fn ($query) => $query->where('sucursal_id', $request->user()->current_sucursal_id)
                ->when($filters['event_type'] ?? null, fn ($query, $event) => $query->where('event_type', $event)))
            ->when($filters['channel'] ?? null, fn ($query, $channel) => $query->where('channel', $channel))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['cliente_id'] ?? null, fn ($query, $client) => $query->where('cliente_id', $client))
            ->when($filters['credito_id'] ?? null, fn ($query, $credit) => $query->where('credito_id', $credit))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->with('event:id,event_type,sucursal_id')->latest();

        return Inertia::render('Communications/Index', [
            'channels' => CommunicationChannel::where('team_id', $teamId)->get()
                ->map(function ($item) {
                    $fields = ['id', 'channel', 'provider', 'enabled', 'eligibility', 'eligibility_reference', 'checked_at'];
                    if ($item->channel !== 'sms') {
                        $fields[] = 'sender';
                    }

                    return $item->only($fields) + ['has_secret' => $item->channel === 'sms'
                        ? app(LabsMobileConfiguration::class)->credentialsConfigured() : filled($item->secret)];
                }),
            'smsConfiguration' => app(LabsMobileConfiguration::class)->presence(),
            'capabilities' => ['email' => app(EmailTransport::class)->capabilities(),
                'sms' => app(LabsMobileSmsTransport::class)->capabilities(),
                'whatsapp' => app(MetaWhatsAppTransport::class)->capabilities()],
            'canReadNotifications' => $request->user()->can('read plantillas-notificaciones'),
            'notificationTemplates' => \App\Models\NotificationTemplate::whereHas('activeVersion')->with(['activeVersion' => $versionSummary])->orderBy('name')->get(['id', 'name', 'channel', 'event_type']),
            'rules' => CommunicationRule::with(['notificationTemplate:id,name,channel,event_type', 'notificationTemplate.activeVersion' => $versionSummary])->where('team_id', $teamId)->orderBy('event_type')->get(),
            'ruleDefaults' => [
                'leadDays' => config('comunicaciones.default_lead_days'),
                'maxLeadDays' => config('comunicaciones.max_lead_days'),
                'timezone' => config('comunicaciones.default_timezone'),
                'windowStart' => config('comunicaciones.default_window_start'),
                'windowEnd' => config('comunicaciones.default_window_end'),
                'dailyLimit' => config('comunicaciones.default_daily_limit'),
                'maxDailyLimit' => config('comunicaciones.max_daily_limit'),
            ],
            'consents' => CommunicationConsent::where('team_id', $teamId)->whereHas('cliente',
                fn ($query) => $query->where('sucursal_id', $request->user()->current_sucursal_id))
                ->when($filters['cliente_id'] ?? null, fn ($query, $client) => $query->where('cliente_id', $client))
                ->latest()->limit(30)->get()->map(fn ($item) => $item->only(['id', 'cliente_id', 'channel', 'purpose', 'source', 'granted_at', 'revoked_at'])),
            'deliveries' => $history->paginate(20)->withQueryString()->through(fn ($item) => $item->only(['id', 'communication_event_id',
                'cliente_id', 'credito_id', 'channel', 'status', 'reason', 'attempted_at', 'accepted_at', 'delivered_at',
                'read_at', 'queued_at', 'created_at', 'provider_message_id', 'provider', 'provider_correlation_id', 'template_version', 'notification_template_version_id']) + ['notification_version' => $item->notificationVersion, 'event_type' => $item->event?->event_type]),
            'filters' => $filters,
            'activeTab' => in_array($request->query('tab'), ['channels', 'rules', 'consents', 'history'], true)
                ? $request->query('tab') : 'channels',
            'notice' => $request->session()->get('success'),
            'canManage' => $request->user()->can('manage comunicaciones'),
            'canTest' => $request->user()->can('send test comunicaciones'),
            'sendingEnabled' => config('comunicaciones.send_enabled') === true,
            'testRecipient' => $request->user()->can('send test comunicaciones') ? config('comunicaciones.test_recipient') : null,
        ]);
    }

    public function configureChannel(ConfigureChannelRequest $request, CommunicationChannelService $service)
    {
        $service->configure($request->validated(), $request->user());

        return back()->with('success', 'Canal actualizado.');
    }

    public function configureRule(ConfigureRuleRequest $request, \App\Services\Communications\CommunicationRuleService $service)
    {
        $service->save($request->validated(), $request->user());

        return back()->with('success', 'Regla y plantilla actualizadas.');
    }

    public function grantConsent(GrantConsentRequest $request, Cliente $cliente, CommunicationOrchestrator $service)
    {
        $service->grantConsent($cliente, $request->user()->current_team_id, $request->validated(), $request->user());

        return back()->with('success', 'Consentimiento registrado.');
    }

    public function revokeConsent(Request $request, Cliente $cliente, CommunicationConsent $consent, CommunicationOrchestrator $service)
    {
        Gate::authorize('manage comunicaciones');
        Gate::authorize('update', $cliente);
        abort_unless($consent->cliente_id === $cliente->id && $consent->team_id === $request->user()->current_team_id, 404);
        $service->revokeConsent($consent, $request->user());

        return back()->with('success', 'Consentimiento revocado.');
    }

    public function sendTest(Request $request, ActivityLogService $activity)
    {
        Gate::authorize('send test comunicaciones');
        $data = $request->validate(['channel' => ['required', 'in:email'], 'recipient' => ['required', 'email']]);
        if (config('comunicaciones.send_enabled') !== true || ! config('comunicaciones.test_recipient')
            || ! hash_equals(mb_strtolower(config('comunicaciones.test_recipient')), mb_strtolower($data['recipient']))
            || in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['recipient' => 'Configura un transporte real y el destinatario de prueba autorizado en el entorno.']);
        }
        $channel = CommunicationChannel::where('team_id', $request->user()->current_team_id)->where('channel', 'email')
            ->where('enabled', true)->where('eligibility', 'approved')->firstOrFail();
        try {
            Mail::to($data['recipient'])->send(new CommunicationMail('Prueba técnica de Kronik',
                'Prueba técnica de correo de Kronik. No contiene datos de clientes.', $channel->sender));
            $result = 'accepted';
        } catch (\Throwable $exception) {
            $result = 'uncertain';
        }
        $activity->log(ActivityEvent::CommunicationTestSent, 'Prueba técnica de canal realizada', $channel,
            ['state' => 'test', 'result' => $result], causer: $request->user(), teamId: $channel->team_id,
            sucursalId: $request->user()->current_sucursal_id, inheritActorContext: false);

        return back()->with('success', $result === 'accepted' ? 'El servidor aceptó la prueba; verifica el buzón.' : 'El resultado es incierto; revisa el proveedor antes de repetir.');
    }

    public function checkConnection(Request $request, ActivityLogService $activity)
    {
        Gate::authorize('send test comunicaciones');
        $data = $request->validate(['channel' => ['required', 'in:sms,whatsapp']]);
        $channel = CommunicationChannel::where('team_id', $request->user()->current_team_id)
            ->where('channel', $data['channel'])->firstOrFail();
        if ($data['channel'] === 'sms') {
            if ($channel->provider !== 'labsmobile') {
                throw ValidationException::withMessages(['channel' => 'Retira primero la configuración SMS anterior con la limpieza de datos de prueba.']);
            }
            if (! app(LabsMobileConfiguration::class)->credentialsConfigured()) {
                throw ValidationException::withMessages(['channel' => 'Configura primero la conexión SMS en el entorno.']);
            }
            $connected = app(LabsMobileSmsTransport::class)->checkConnection();
        } else {
            $settings = json_decode($channel->secret ?? '{}', true);
            if (! is_array($settings) || collect(['token', 'api_version', 'waba_id'])->contains(fn ($key) => ! filled($settings[$key] ?? null))) {
                throw ValidationException::withMessages(['channel' => 'Configura primero las credenciales del proveedor.']);
            }
            try {
                $response = Http::timeout(10)->withToken($settings['token'])
                    ->get('https://graph.facebook.com/'.$settings['api_version'].'/'.$settings['waba_id'].'/message_templates', ['limit' => 1]);
                $connected = $response->successful() && is_array($response->json('data'));
            } catch (\Throwable $exception) {
                $connected = false;
            }
        }
        DB::transaction(function () use ($channel, $connected, $activity, $request) {
            if ($connected || $channel->channel === 'sms') {
                $channel->update(['checked_at' => $connected ? now() : null]);
            }
            $activity->log(ActivityEvent::CommunicationTestSent, 'Conexión de canal comprobada', $channel,
                ['state' => 'connection_check', 'result' => $connected ? 'accepted' : 'failed'],
                causer: $request->user(), teamId: $channel->team_id,
                sucursalId: $request->user()->current_sucursal_id, inheritActorContext: false);
        });

        return back()->with('success', $connected
            ? 'La API aceptó la consulta técnica. Esto no confirma elegibilidad ni entrega de mensajes.'
            : 'No se pudo comprobar la conexión. Revisa credenciales, permisos y estado del proveedor.');
    }
}
