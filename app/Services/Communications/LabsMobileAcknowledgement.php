<?php

namespace App\Services\Communications;

use Carbon\CarbonImmutable;
use Throwable;

/** Normalized SMS evidence. Parsing does not authenticate its origin. */
final readonly class LabsMobileAcknowledgement
{
    private function __construct(
        public string $correlationId,
        public string $recipient,
        public string $level,
        public string $result,
        public ?string $code,
        public CarbonImmutable $occurredAt,
    ) {}

    public static function fromPayload(array $payload): ?self
    {
        foreach (['subid', 'msisdn', 'acklevel', 'status', 'timestamp'] as $field) {
            if (! isset($payload[$field]) || ! is_string($payload[$field])) {
                return null;
            }
        }
        $level = $payload['acklevel'];
        $result = $payload['status'];
        $code = $payload['desc'] ?? null;
        if (! preg_match('/\A[a-f0-9]{20}\z/', $payload['subid'])
            || ! preg_match('/\A[1-9][0-9]{7,14}\z/', $payload['msisdn'])
            || ($payload['type'] ?? 'sms') !== 'sms'
            || ! in_array($level, ['gateway', 'operator', 'handset', 'error'], true)
            || $result !== ($level === 'error' ? 'ko' : 'ok')
            || ($code !== null && ! in_array($code, ['', 'DELIVRD', 'REJECTD', 'EXPIRED', 'BLOCKED', 'UNDELIV', 'UNKNOWN'], true))
            || ($level === 'error' && $code === 'DELIVRD')
            || ($level !== 'error' && ! in_array($code, [null, '', 'DELIVRD'], true))) {
            return null;
        }
        try {
            $time = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $payload['timestamp'], 'UTC');
            if (! $time || $time->format('Y-m-d H:i:s') !== $payload['timestamp']) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return new self($payload['subid'], '+'.$payload['msisdn'], $level, $result, $code ?: null, $time);
    }

    public function status(): string
    {
        return match ($this->level) {
            'handset' => 'delivered', 'error' => 'failed', default => 'accepted',
        };
    }

    public function eventKey(): string
    {
        return hash('sha256', json_encode(['labsmobile', $this->correlationId, $this->level,
            $this->result, $this->code, $this->occurredAt->format('Y-m-d H:i:s')], JSON_THROW_ON_ERROR));
    }
}
