<?php

use App\Services\SICs\SicFirma;

test('SIC signature verifies exact raw bytes and rejects altered bodies signatures and wrong curve', function () {
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'secp384r1', 'config' => __DIR__.'/../Fixtures/Sic/openssl.cnf']);
    if ($key === false) {
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }
        throw new RuntimeException(implode('; ', $errors));
    }
    openssl_pkey_export($key, $private, null, ['config' => __DIR__.'/../Fixtures/Sic/openssl.cnf']);
    $public = openssl_pkey_get_details($key)['key'];
    $firma = new SicFirma;
    $body = '{"folio":"123","persona":{"nombres":"PRUEBA"}}';
    $signature = $firma->firmar($body, $private);
    expect(ctype_xdigit($signature))->toBeTrue()
        ->and($firma->verificar($body, $signature, $public))->toBeTrue()
        ->and($firma->verificar($body.' ', $signature, $public))->toBeFalse()
        ->and($firma->verificar($body, 'zz', $public))->toBeFalse()
        ->and($firma->verificar($body, '', $public))->toBeFalse();
    $other = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1', 'config' => __DIR__.'/../Fixtures/Sic/openssl.cnf']);
    openssl_pkey_export($other, $wrong, null, ['config' => __DIR__.'/../Fixtures/Sic/openssl.cnf']);
    expect(fn () => $firma->firmar($body, $wrong))->toThrow(RuntimeException::class);
});
