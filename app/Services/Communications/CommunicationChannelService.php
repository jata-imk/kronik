<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Models\CommunicationChannel;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommunicationChannelService
{
    public function __construct(private ActivityLogService $activity) {}

    public function configure(array $data, User $actor): CommunicationChannel
    {
        $teamId = $actor->current_team_id;
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

        return DB::transaction(function () use ($data, $teamId, $actor) {
            $item = CommunicationChannel::firstOrNew(['team_id' => $teamId, 'channel' => $data['channel']]);
            if ($data['channel'] === 'sms' && $item->exists && ($item->provider !== 'labsmobile' || $item->getRawOriginal('secret') !== null)) {
                throw ValidationException::withMessages(['channel' => 'Retira primero la configuración SMS anterior con la limpieza de datos de prueba.']);
            }
            $previousSender = $item->sender;
            $item->provider = match ($data['channel']) {
                'email' => 'smtp', 'sms' => 'labsmobile', 'whatsapp' => 'meta_cloud'
            };
            $item->sender = $data['channel'] === 'sms' ? null : $data['sender'];
            $item->eligibility = $data['eligibility'];
            $item->eligibility_reference = $data['eligibility_reference'] ?? null;
            $item->enabled = $data['enabled'];
            if ($data['channel'] === 'whatsapp' && filled($data['secret'] ?? null)) {
                $json = json_decode($data['secret'], true);
                $valid = is_array($json) && filled($json['token'] ?? null) && filled($json['api_version'] ?? null)
                    && filled($json['phone_number_id'] ?? null) && filled($json['waba_id'] ?? null) && filled($json['app_secret'] ?? null)
                    && filled($json['verify_token'] ?? null) && (string) $json['phone_number_id'] === $data['sender'];
                if (! $valid) {
                    throw ValidationException::withMessages(['secret' => 'La configuración del proveedor está incompleta o no coincide con el remitente.']);
                }
                if ($data['channel'] === 'whatsapp' && (! preg_match('/\Av\d+\.\d+\z/', $json['api_version'])
                    || ! ctype_digit((string) $json['waba_id']) || ! ctype_digit((string) $json['phone_number_id']))) {
                    throw ValidationException::withMessages(['secret' => 'La versión Graph y los identificadores de Meta deben ser válidos.']);
                }
                $item->secret = $data['secret'];
                $item->checked_at = null;
            }
            if ($previousSender !== $item->sender) {
                $item->checked_at = null;
            }
            if ($item->enabled && $data['channel'] === 'whatsapp' && ! $item->secret) {
                throw ValidationException::withMessages(['secret' => 'Faltan las credenciales del proveedor.']);
            }
            if ($item->enabled && $data['channel'] === 'sms' && ! app(LabsMobileConfiguration::class)->configured()) {
                throw ValidationException::withMessages(['channel' => 'Completa la configuración SMS del entorno antes de habilitar el canal.']);
            }
            $item->save();
            $this->activity->log(ActivityEvent::CommunicationChannelConfigured, 'Canal de comunicación configurado', $item,
                ['state' => $item->enabled ? 'enabled' : 'disabled', 'provider' => $item->provider],
                causer: $actor, teamId: $teamId, sucursalId: $actor->current_sucursal_id, inheritActorContext: false);

            return $item;
        });

    }
}
