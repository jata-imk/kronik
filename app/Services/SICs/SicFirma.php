<?php

namespace App\Services\SICs;

use RuntimeException;

/** Production-compatible primitive; it does not enable production transport. */
final class SicFirma
{
    public function firmar(string $body, string $privateKey, string $passphrase = ''): string
    {
        $key = openssl_pkey_get_private($privateKey, $passphrase);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (! $key || ($details['ec']['curve_name'] ?? null) !== 'secp384r1'
            || ! openssl_sign($body, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar la petición SIC con una llave P-384 válida.');
        }

        return bin2hex($signature);
    }

    public function verificar(string $body, string $signature, string $publicKey): bool
    {
        if (! $signature || strlen($signature) % 2 !== 0 || ! ctype_xdigit($signature)) {
            return false;
        }
        $key = openssl_pkey_get_public($publicKey);
        $details = $key ? openssl_pkey_get_details($key) : false;

        return $key && ($details['ec']['curve_name'] ?? null) === 'secp384r1'
            && openssl_verify($body, hex2bin($signature), $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
