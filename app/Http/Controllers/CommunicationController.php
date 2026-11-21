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
use App\Services\Communications\CommunicationOrchestrator;
use App\Services\Communications\EmailTransport;
use App\Services\Communications\InfobipSmsTransport;
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
        $history = CommunicationDelivery::where('team_id', $teamId)
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
                ->map(fn ($item) => $item->only(['id', 'channel', 'provider', 'sender', 'enabled', 'eligibility', 'eligibility_reference', 'checked_at'])
                    + ['has_secret' => filled($item->secret)]),
            'capabilities' => ['email' => app(EmailTransport::class)->capabilities(),
                'sms' => app(InfobipSmsTransport::class)->capabilities(),
                'whatsapp' => app(MetaWhatsAppTransport::class)->capabilities()],
            'rules' => CommunicationRule::where('team_id', $teamId)->orderBy('event_type')->get(),
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
                'read_at', 'queued_at', 'created_at', 'provider_message_id', 'template_version']) + ['event_type' => $item->event?->event_type]),
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

    public function configureChannel(ConfigureChannelRequest $request, ActivityLogService $activity)
    {
        $data = $request->validated();
        $teamId = $request->user()->current_team_id;
        if ($data['channel'] === 'email') {
            if (! filter_var($data['sender'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['sender' => 'Indica un correo remitente válido.']);
            }
            if (($data['secret'] ?? null) !== null) {
                throw ValidationException::withMessages(['secret' => 'Configura las credenciales SMTP en el entorno, no en este formulario.']);
            }
        } elseif ($data['channel'] === 'whatsapp' && ! ctype_digit($data['sender'])) {
            throw ValidationException::withMessages(['sender' => 'Indica el ID numérico del teléfono de WhatsApp Business.']);
        }
        if ($data['enabled'] && $data['eligibility'] !== 'approved') {
            throw ValidationException::withMessages(['enabled' => 'Confirma la elegibilidad del proveedor antes de activar el canal.']);
        }
        $item = DB::transaction(function () use ($data, $teamId, $request, $activity) {
            $item = CommunicationChannel::firstOrNew(['team_id' => $teamId, 'channel' => $data['channel']]);
            $previousSender = $item->sender;
            $item->provider = match ($data['channel']) {
                'email' => 'smtp', 'sms' => 'infobip', 'whatsapp' => 'meta_cloud'
            };
            $item->sender = $data['sender'];
            $item->eligibility = $data['eligibility'];
            $item->eligibility_reference = $data['eligibility_reference'] ?? null;
            $item->enabled = $data['enabled'];
            if ($data['channel'] !== 'email' && filled($data['secret'] ?? null)) {
                $json = json_decode($data['secret'], true);
                $valid = $data['channel'] === 'whatsapp'
                    ? is_array($json) && filled($json['token'] ?? null) && filled($json['api_version'] ?? null)
                        && filled($json['phone_number_id'] ?? null) && filled($json['waba_id'] ?? null) && filled($json['app_secret'] ?? null)
                        && filled($json['verify_token'] ?? null) && (string) $json['phone_number_id'] === $data['sender']
                    : is_array($json) && filled($json['api_key'] ?? null) && filled($json['base_url'] ?? null)
                        && filled($json['webhook_secret'] ?? null);
                if (! $valid) {
                    throw ValidationException::withMessages(['secret' => 'La configuración del proveedor está incompleta o no coincide con el remitente.']);
                }
                if ($data['channel'] === 'sms' && ! preg_match('/\A[a-z0-9-]+\.api(?:-[a-z0-9]+)?\.infobip\.com\z/i', $json['base_url'])) {
                    throw ValidationException::withMessages(['secret' => 'Indica el host de API asignado a la cuenta Infobip.']);
                }
                if ($data['channel'] === 'whatsapp' && (! preg_match('/\Av\d+\.\d+\z/', $json['api_version'])
                    || ! ctype_digit((string) $json['waba_id']) || ! ctype_digit((string) $json['phone_number_id']))) {
                    throw ValidationException::withMessages(['secret' => 'La versión Graph y los identificadores de Meta deben ser válidos.']);
                }
                $item->secret = $data['secret'];
                $item->checked_at = null;
            }
            if ($previousSender !== $data['sender']) {
                $item->checked_at = null;
            }
            if ($item->enabled && $data['channel'] !== 'email' && ! $item->secret) {
                throw ValidationException::withMessages(['secret' => 'Faltan las credenciales del proveedor.']);
            }
            $item->save();
            $activity->log(ActivityEvent::CommunicationChannelConfigured, 'Canal de comunicación configurado', $item,
                ['state' => $item->enabled ? 'enabled' : 'disabled', 'provider' => $item->provider],
                causer: $request->user(), teamId: $teamId, sucursalId: $request->user()->current_sucursal_id, inheritActorContext: false);

            return $item;
        });

        return back()->with('success', 'Canal actualizado.');
    }

    public function configureRule(ConfigureRuleRequest $request, ActivityLogService $activity)
    {
        $data = $request->validated();
        foreach ([$data['subject'] ?? '', $data['body']] as $text) {
            preg_match_all('/\{([^{}]+)\}/u', $text, $matches);
            if (array_diff($matches[1], ['nombre', 'fecha_vencimiento'])) {
                throw ValidationException::withMessages(['body' => 'La plantilla contiene una variable no permitida.']);
            }
        }
        $teamId = $request->user()->current_team_id;
        DB::transaction(function () use ($data, $teamId, $request, $activity) {
            $item = CommunicationRule::firstOrNew(['team_id' => $teamId, 'event_type' => $data['event_type'], 'channel' => $data['channel']]);
            $old = $item->exists ? [$item->subject, $item->body, $item->external_template_name, $item->external_template_locale] : null;
            if ($data['event_type'] === 'payment_received') {
                $data['lead_days'] = config('comunicaciones.default_lead_days');
            }
            $item->fill($data);
            if ($old && $old !== [$item->subject, $item->body, $item->external_template_name, $item->external_template_locale]) {
                $item->template_version++;
            }
            $item->save();
            $activity->log(ActivityEvent::CommunicationRuleConfigured, 'Regla de comunicación configurada', $item,
                ['state' => $item->enabled ? 'enabled' : 'disabled'], causer: $request->user(),
                teamId: $teamId, sucursalId: $request->user()->current_sucursal_id, inheritActorContext: false);
        });

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
        $settings = json_decode($channel->secret ?? '{}', true);
        $required = $data['channel'] === 'sms' ? ['api_key', 'base_url'] : ['token', 'api_version', 'waba_id'];
        if (! is_array($settings) || collect($required)->contains(fn ($key) => ! filled($settings[$key] ?? null))) {
            throw ValidationException::withMessages(['channel' => 'Configura primero las credenciales del proveedor.']);
        }
        try {
            $response = $data['channel'] === 'sms'
                ? Http::timeout(10)->withHeaders(['Authorization' => 'App '.$settings['api_key']])
                    ->get('https://'.$settings['base_url'].'/sms/3/logs', ['limit' => 1])
                : Http::timeout(10)->withToken($settings['token'])
                    ->get('https://graph.facebook.com/'.$settings['api_version'].'/'.$settings['waba_id'].'/message_templates', ['limit' => 1]);
            $connected = $response->successful() && is_array($response->json('results') ?? $response->json('data'));
        } catch (\Throwable $exception) {
            $connected = false;
        }
        DB::transaction(function () use ($channel, $connected, $activity, $request) {
            if ($connected) {
                $channel->update(['checked_at' => now()]);
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
