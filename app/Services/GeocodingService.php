<?php

namespace App\Services;

use App\Interfaces\GeocodingServiceInterface;
use Illuminate\Support\Facades\Http;

class GeocodingService implements GeocodingServiceInterface
{
    public function search(string $query): ?array
    {
        if (! config('services.geocoding.enabled')) {
            return null;
        }

        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'User-Agent' => 'Kronik/1.0 (hola@aleliz.xyz)',
            'verify' => false,
        ])->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'jsonv2',
            'polygon_geojson' => '1',
            'featureType' => 'administrative',
            'limit' => '1',
            'q' => $query,
        ]);

        if (! $response->ok()) {
            return null;
        }

        $response = $response->json();

        if (empty($response)) {
            return null;
        }

        return $response;
    }
}
