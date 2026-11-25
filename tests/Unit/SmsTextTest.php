<?php

use App\Services\Communications\SmsText;

test('cuenta unidades GSM Unicode y caracteres completos en las fronteras', function ($text, $segments, $unicode) {
    expect((new SmsText)->measure($text))->toBe(['segments' => $segments, 'unicode' => $unicode]);
})->with([
    'vacío' => ['', 0, false],
    'GSM 160' => [str_repeat('a', 160), 1, false],
    'GSM 161' => [str_repeat('a', 161), 2, false],
    'GSM 306' => [str_repeat('a', 306), 2, false],
    'GSM 307' => [str_repeat('a', 307), 3, false],
    'extendidos' => [str_repeat('^', 80), 1, false],
    'escape en frontera' => [str_repeat('a', 152).'^'.str_repeat('a', 152), 3, false],
    'Unicode 70' => [str_repeat('á', 70), 1, true],
    'Unicode 71' => [str_repeat('á', 71), 2, true],
    'Unicode 134' => [str_repeat('á', 134), 2, true],
    'Unicode 135' => [str_repeat('á', 135), 3, true],
    'emoji doble unidad' => [str_repeat('😀', 35), 1, true],
    'pares completos' => [str_repeat('á', 66).'😀'.str_repeat('á', 66), 3, true],
    'acentos GSM' => ['éñèà', 1, false],
]);
