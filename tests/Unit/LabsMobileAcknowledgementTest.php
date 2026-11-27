<?php

use App\Services\Communications\LabsMobileAcknowledgement;

test('normaliza únicamente evidencia SMS conocida con fecha UTC sin acreditar lectura', function ($level, $result, $code, $status) {
    $ack = LabsMobileAcknowledgement::fromPayload(['subid' => str_repeat('a', 20), 'msisdn' => '525512345678',
        'acklevel' => $level, 'status' => $result, 'desc' => $code, 'timestamp' => '2026-11-25 14:30:00']);
    expect($ack)->not->toBeNull()->and($ack->status())->toBe($status)
        ->and($ack->recipient)->toBe('+525512345678')->and($ack->occurredAt->timezoneName)->toBe('UTC')
        ->and($ack->eventKey())->toHaveLength(64);
})->with([
    ['gateway', 'ok', null, 'accepted'], ['operator', 'ok', null, 'accepted'],
    ['handset', 'ok', 'DELIVRD', 'delivered'], ['error', 'ko', 'EXPIRED', 'failed'],
]);

test('rechaza estados formatos y combinaciones que no prueban el resultado SMS', function ($changes) {
    $payload = ['subid' => str_repeat('a', 20), 'msisdn' => '525512345678', 'acklevel' => 'handset',
        'status' => 'ok', 'timestamp' => '2026-11-25 14:30:00'];
    expect(LabsMobileAcknowledgement::fromPayload(array_replace($payload, $changes)))->toBeNull();
})->with([
    [['subid' => 'unknown']], [['subid' => []]], [['msisdn' => '+525512345678']], [['msisdn' => 'abc']],
    [['msisdn' => 525512345678]], [['acklevel' => 'read']], [['type' => 'rcs']], [['desc' => 'READ']],
    [['desc' => ['EXPIRED']]], [['desc' => 'untrusted free text']], [['acklevel' => 'handset', 'status' => 'ko']],
    [['acklevel' => 'error', 'status' => 'ok']], [['acklevel' => 'error', 'status' => 'ko', 'desc' => 'DELIVRD']],
    [['desc' => 'EXPIRED']], [['timestamp' => '2026-02-30 00:00:00']], [['timestamp' => '2026-11-25T14:30:00Z']],
    [['timestamp' => '2026-11-25 14:30:00 trailing']], [['timestamp' => null]],
]);
