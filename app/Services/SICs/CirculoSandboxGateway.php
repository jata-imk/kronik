<?php

namespace App\Services\SICs;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class CirculoSandboxGateway implements SicGateway
{
    public function consultar(string $servicio, array $peticion): array
    {
        self::habilitado();
        $ruta = config('sic.servicios.'.$servicio.'.ruta');
        if (! $ruta) {
            throw ValidationException::withMessages(['sic' => 'Este servicio SIC no está implementado.']);
        }
        // No retries: a POST may already have been processed by the provider.
        $response = Http::acceptJson()->withHeaders(['x-api-key' => config('sic.api_key')])
            ->connectTimeout(config('sic.connect_timeout'))->timeout(config('sic.timeout'))
            ->withOptions(['verify' => config('sic.ca_bundle') ?: true, 'allow_redirects' => false])
            ->post(rtrim(config('sic.host'), '/').$ruta, $peticion);
        $body = $response->json();
        $resultado = $response->status() === 204
            ? ['estado' => 'sin_score', 'scores' => [], 'razones' => [], 'secciones' => [], 'folio' => null]
            : ($response->successful() && is_array($body) ? app(SicResultado::class)->normalizar($body) : null);

        return ['http_status' => $response->status(), 'respuesta' => is_array($body) ? $body : ['contenido_no_json' => $response->body()],
            'resultado' => $resultado ?? ['estado' => 'error', 'scores' => [], 'razones' => [], 'secciones' => [], 'folio' => null]];
    }

    public static function habilitado(): void
    {
        if (config('sic.ca_bundle') && (! is_string(config('sic.ca_bundle')) || ! is_readable(config('sic.ca_bundle')))) {
            throw ValidationException::withMessages(['sic' => 'El archivo de autoridades certificadoras SIC no está disponible. Configura un archivo de confianza; no desactives TLS.']);
        }
        if (config('sic.qa_habilitado') !== true || config('sic.sandbox') !== true
            || rtrim((string) config('sic.host'), '/') !== 'https://services.circulodecredito.com.mx'
            || ! config('sic.api_key')) {
            throw ValidationException::withMessages(['sic' => 'Las consultas SIC QA requieren habilitación, credenciales sandbox y el destino autorizado. Producción permanece cerrada.']);
        }
    }
}
