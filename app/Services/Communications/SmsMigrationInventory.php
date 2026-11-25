<?php

namespace App\Services\Communications;

use App\Jobs\SendCommunicationDelivery;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Observes storage without loading models, decrypting credentials or deserializing jobs.
 * Queue candidates belong to communications in general, not necessarily to SMS.
 */
final class SmsMigrationInventory
{
    private const PROVIDERS = ['infobip', 'labsmobile', 'meta_cloud', 'smtp'];

    private const STATUSES = ['prepared', 'queued', 'sending', 'accepted', 'delivered', 'read', 'failed', 'cancelled', 'uncertain'];

    public function collect(): array
    {
        $startedAt = now()->toISOString();
        $channels = $this->inspect(function () {
            $db = DB::connection();
            if (! $db->getSchemaBuilder()->hasTable('communication_channels')) {
                return $this->unavailable('missing_table');
            }

            $counts = $db->table('communication_channels')->where('channel', 'sms')
                ->selectRaw('provider, count(*) as total')->groupBy('provider')->get();

            return ['status' => 'available', 'total' => (int) $counts->sum('total'),
                'enabled' => $db->table('communication_channels')->where('channel', 'sms')->where('enabled', true)->count(),
                'with_stored_secret' => $db->table('communication_channels')->where('channel', 'sms')
                    ->whereNotNull('secret')->where('secret', '<>', '')->count(),
                'by_provider' => $this->providerCounts($counts)];
        });
        $deliveries = $this->inspect(function () {
            $db = DB::connection();
            if (! $db->getSchemaBuilder()->hasTable('communication_deliveries')) {
                return $this->unavailable('missing_table');
            }
            $query = $db->table('communication_deliveries')->where('channel', 'sms');
            $states = array_fill_keys([...self::STATUSES, 'other'], 0);
            foreach ((clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->get() as $row) {
                $key = in_array($row->status, self::STATUSES, true) ? $row->status : 'other';
                $states[$key] += (int) $row->total;
            }

            return ['status' => 'available', 'total' => array_sum($states), 'by_status' => $states,
                'with_external_id' => (clone $query)->whereNotNull('provider_message_id')->where('provider_message_id', '<>', '')->count(),
                'provider_provenance' => $db->getSchemaBuilder()->hasColumn('communication_deliveries', 'provider')
                    ? 'requires_evidence_review' : 'not_recorded'];
        });
        $webhooks = $this->inspect(function () {
            $db = DB::connection();
            if (! $db->getSchemaBuilder()->hasTable('communication_webhook_events')) {
                return $this->unavailable('missing_table');
            }
            // Provider is recorded here; do not assign unknown callbacks to SMS by assumption.
            $query = $db->table('communication_webhook_events');
            $counts = (clone $query)->selectRaw('provider, count(*) as total')->groupBy('provider')->get();

            return ['status' => 'available', 'scope' => 'all_communication_providers',
                'total' => (int) $counts->sum('total'), 'by_provider' => $this->providerCounts($counts),
                'unmatched' => (clone $query)->whereNull('communication_delivery_id')->count()];
        });

        $connections = config('queue.connections', []);
        $activeDriver = is_array($connections) ? ($connections[config('queue.default')]['driver'] ?? null) : null;
        $queues = [];
        foreach (is_array($connections) ? $connections : [] as $settings) {
            if (is_array($settings) && ($settings['driver'] ?? null) === 'database') {
                // Inspect configured database spools even when the active driver is sync or Redis.
                $queues[] = $this->inspect(fn () => $this->queuedJobs($settings));
            }
        }
        $failed = $this->inspect(fn () => $this->failedJobs());
        $sections = [$channels, $deliveries, $webhooks, ...$queues, $failed];
        $activeInspectable = in_array($activeDriver, ['database', 'sync', 'null'], true)
            && ($activeDriver !== 'database' || $queues !== []);
        $complete = $activeInspectable && ! in_array(false, array_map(
            fn (array $section) => $section['status'] === 'available' && ($section['unclassified'] ?? 0) === 0,
            $sections,
        ), true);

        return [
            'schema_version' => 1,
            'started_at' => $startedAt,
            'finished_at' => now()->toISOString(),
            'read_only' => true,
            'external_sending_enabled' => config('comunicaciones.send_enabled') === true,
            'channels' => $channels,
            'deliveries' => $deliveries,
            'webhooks' => $webhooks,
            'queue' => [
                'active_driver' => in_array($activeDriver, ['database', 'sync', 'null', 'redis', 'sqs', 'beanstalkd'], true) ? $activeDriver : 'other',
                'active_inspectable' => $activeInspectable,
                'scope' => 'communication_jobs_all_channels',
                'database_stores' => $queues,
            ],
            'failed_jobs' => $failed,
            'complete' => $complete,
            // This observation is not a stopped-system snapshot or authorization to change providers.
            'cutover_ready' => false,
            'limitations' => [
                'Los candidatos de cola incluyen correo y WhatsApp; no se atribuyen automáticamente a SMS.',
                'No se inspeccionan Redis, SQS ni Beanstalkd; tampoco almacenes que ya no figuren en la configuración.',
                'La observación puede cambiar mientras trabajan el scheduler y los workers.',
                'Los conteos no acreditan procedencia histórica, entrega ni autorización del corte.',
            ],
        ];
    }

    private function queuedJobs(array $settings): array
    {
        $db = DB::connection($settings['connection'] ?? null);
        $table = $settings['table'] ?? 'jobs';
        if (! is_string($table) || ! $db->getSchemaBuilder()->hasTable($table)) {
            return $this->unavailable('missing_table');
        }
        $counts = ['status' => 'available', 'ready' => 0, 'delayed' => 0, 'reserved' => 0, 'unrelated' => 0, 'unclassified' => 0];
        $timestamp = now()->timestamp;
        $db->table($table)->select(['id', 'payload', 'available_at', 'reserved_at'])->orderBy('id')
            ->chunkById(100, function ($rows) use (&$counts, $timestamp) {
                foreach ($rows as $row) {
                    $kind = $this->jobKind($row->payload);
                    if ($kind !== 'communication') {
                        $counts[$kind]++;

                        continue;
                    }
                    $key = $row->reserved_at !== null ? 'reserved' : ((int) $row->available_at > $timestamp ? 'delayed' : 'ready');
                    $counts[$key]++;
                }
            });

        return $counts;
    }

    private function failedJobs(): array
    {
        $driver = config('queue.failed.driver');
        if (! in_array($driver, ['database', 'database-uuids'], true)) {
            return $this->unavailable('unsupported_or_disabled_storage');
        }
        $db = DB::connection(config('queue.failed.database'));
        $table = config('queue.failed.table', 'failed_jobs');
        if (! is_string($table) || ! $db->getSchemaBuilder()->hasTable($table)) {
            return $this->unavailable('missing_table');
        }
        $counts = ['status' => 'available', 'communication' => 0, 'unrelated' => 0, 'unclassified' => 0];
        $db->table($table)->select(['id', 'payload'])->orderBy('id')->chunkById(100, function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                $counts[$this->jobKind($row->payload)]++;
            }
        });

        return $counts;
    }

    private function jobKind(mixed $payload): string
    {
        if (! is_string($payload)) {
            return 'unclassified';
        }
        $data = json_decode($payload, true, 32);
        if (! is_array($data)) {
            return 'unclassified';
        }
        $names = array_values(array_filter([$data['data']['commandName'] ?? null, $data['displayName'] ?? null],
            fn ($name) => is_string($name) && $name !== ''));
        if ($names === [] || (in_array(SendCommunicationDelivery::class, $names, true) && count(array_unique($names)) > 1)) {
            return 'unclassified';
        }

        return in_array(SendCommunicationDelivery::class, $names, true) ? 'communication' : 'unrelated';
    }

    private function providerCounts(iterable $rows): array
    {
        $counts = array_fill_keys([...self::PROVIDERS, 'unknown', 'other'], 0);
        foreach ($rows as $row) {
            $key = in_array($row->provider, self::PROVIDERS, true) ? $row->provider
                : ($row->provider === null || $row->provider === '' ? 'unknown' : 'other');
            $counts[$key] += (int) $row->total;
        }

        return $counts;
    }

    private function inspect(callable $read): array
    {
        try {
            return $read();
        } catch (Throwable) {
            // Database errors can include hosts, paths or parameters; never echo their messages.
            return $this->unavailable('read_failed');
        }
    }

    private function unavailable(string $reason): array
    {
        return ['status' => 'unavailable', 'reason' => $reason];
    }
}
