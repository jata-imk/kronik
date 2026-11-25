<?php

namespace App\Services\Communications;

use App\Enums\ActivityEvent;
use App\Jobs\SendCommunicationDelivery;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationRule;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SmsTestDataCleanup
{
    public function __construct(private ActivityLogService $activity) {}

    /** The only retained legacy rows are inert deliveries: they also neutralize unidentified queued jobs. */
    public function run(bool $apply = false): array
    {
        if ($apply && (config('comunicaciones.send_enabled') !== false || config('comunicaciones.sms_send_enabled') !== false)) {
            throw new RuntimeException('Desactiva las dos barreras de envío antes de limpiar SMS de prueba.');
        }

        return DB::transaction(function () use ($apply) {
            $channels = CommunicationChannel::where('channel', 'sms')->where(function ($query) {
                $query->whereNull('provider')->orWhere('provider', '!=', 'labsmobile')->orWhereNotNull('secret');
            })->lockForUpdate()->get();
            $deliveries = CommunicationDelivery::where('channel', 'sms')->where(function ($query) {
                $query->whereNull('provider')->orWhere('provider', '!=', 'labsmobile');
            })->lockForUpdate()->get();
            $ids = $deliveries->modelKeys();
            $teams = $channels->pluck('team_id')->merge($deliveries->where('status', '!=', 'cancelled')->pluck('team_id'))->unique();
            // A later run must not disable rules belonging to a newly configured LabsMobile channel.
            $configuredTeams = CommunicationChannel::where('channel', 'sms')->where('provider', 'labsmobile')
                ->whereNull('secret')->pluck('team_id');
            $teams = $teams->diff($configuredTeams);
            $report = ['channels' => $channels->count(),
                'deliveries' => $deliveries->where('status', '!=', 'cancelled')->count(),
                'rules' => CommunicationRule::where('channel', 'sms')->whereIn('team_id', $teams)->where('enabled', true)->count(),
                'callbacks' => DB::table('communication_webhook_events')->where('provider', 'infobip')->count(),
                'jobs' => 0, 'unclassified_jobs' => 0, 'uninspected_stores' => 0];
            foreach ($this->queueTables($report) as $table) {
                DB::table($table)->select(['id', 'payload'])->orderBy('id')->chunkById(100, function ($jobs) use ($ids, $table, $apply, &$report) {
                    foreach ($jobs as $job) {
                        $deliveryId = $this->deliveryId($job->payload);
                        if ($deliveryId === null) {
                            $report['unclassified_jobs']++;
                        } elseif (in_array($deliveryId, $ids, true)) {
                            $report['jobs']++;
                            if ($apply) {
                                DB::table($table)->where('id', $job->id)->where('payload', $job->payload)->delete();
                                $delivery = CommunicationDelivery::findOrFail($deliveryId);
                                $this->activity->log(ActivityEvent::CommunicationSmsTestDataCleaned, 'Job SMS anterior de prueba retirado', $delivery,
                                    ['state' => 'job_removed', 'result' => 'completed'], teamId: $delivery->team_id,
                                    sucursalId: $delivery->event?->sucursal_id, inheritActorContext: false);
                            }
                        }
                    }
                });
            }
            if (! $apply) {
                return $report;
            }
            foreach ($deliveries as $delivery) {
                if ($delivery->status !== 'cancelled') {
                    $delivery->update(['status' => 'cancelled', 'reason' => 'sms_provider_replaced']);
                    $this->activity->log(ActivityEvent::CommunicationDeliveryCancelled, 'Salida SMS de prueba invalidada', $delivery,
                        ['state' => 'cancelled'], teamId: $delivery->team_id,
                        sucursalId: $delivery->event?->sucursal_id, inheritActorContext: false);
                }
            }
            foreach ($channels as $channel) {
                $this->activity->log(ActivityEvent::CommunicationSmsTestDataCleaned, 'Configuración SMS de prueba retirada', $channel,
                    ['state' => 'removed', 'result' => 'completed'], teamId: $channel->team_id, inheritActorContext: false);
                $channel->delete(); // Never decrypt the old secret, even if its encryption key was discarded.
            }
            foreach (CommunicationRule::where('channel', 'sms')->whereIn('team_id', $teams)->where('enabled', true)->lockForUpdate()->get() as $rule) {
                $rule->update(['enabled' => false]);
                $this->activity->log(ActivityEvent::CommunicationRuleConfigured, 'Regla SMS de prueba desactivada', $rule,
                    ['state' => 'disabled'], teamId: $rule->team_id, inheritActorContext: false);
            }
            foreach (DB::table('communication_webhook_events')->where('provider', 'infobip')->distinct()->pluck('team_id') as $teamId) {
                DB::table('communication_webhook_events')->where('provider', 'infobip')->where('team_id', $teamId)->delete();
                $this->activity->log(ActivityEvent::CommunicationSmsTestDataCleaned, 'Callbacks SMS anteriores de prueba retirados',
                    metadata: ['state' => 'callbacks_removed', 'result' => 'completed'], teamId: $teamId, inheritActorContext: false);
            }

            return $report;
        });
    }

    /** Inspect only database spools sharing the domain transaction; never purge a shared queue. */
    private function queueTables(array &$report): array
    {
        $tables = [];
        foreach (config('queue.connections', []) as $settings) {
            if (($settings['driver'] ?? null) === 'database') {
                if (($settings['connection'] ?? DB::getDefaultConnection()) !== DB::getDefaultConnection()) {
                    $report['uninspected_stores']++;

                    continue;
                }
                $tables[] = $settings['table'] ?? 'jobs';
            } elseif (! in_array($settings['driver'] ?? null, ['sync', 'null'], true)) {
                $report['uninspected_stores']++;
            }
        }
        $failed = config('queue.failed', []);
        if (in_array($failed['driver'] ?? null, ['database', 'database-uuids'], true)
            && ($failed['database'] ?? DB::getDefaultConnection()) === DB::getDefaultConnection()) {
            $tables[] = $failed['table'] ?? 'failed_jobs';
        } elseif (($failed['driver'] ?? 'null') !== 'null') {
            $report['uninspected_stores']++;
        }

        return array_values(array_unique(array_filter($tables, fn ($table) => is_string($table) && DB::getSchemaBuilder()->hasTable($table))));
    }

    /** Read a single known public integer from a positively identified job; never unserialize PHP. */
    private function deliveryId(string $payload): ?int
    {
        if (strlen($payload) > 2_000_000) {
            return null;
        }
        $data = json_decode($payload, true, 32);
        $class = SendCommunicationDelivery::class;
        if (! is_array($data) || ($data['displayName'] ?? null) !== $class || ($data['data']['commandName'] ?? null) !== $class) {
            return null;
        }
        $command = $data['data']['command'] ?? null;
        if (! is_string($command) || ! str_starts_with($command, 'O:'.strlen($class).':"'.$class.'":')
            || preg_match_all('/(?:[;{])s:10:"deliveryId";i:([1-9][0-9]*);/', $command, $matches) !== 1) {
            return null;
        }

        return filter_var($matches[1][0], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
