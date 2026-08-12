<?php

use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;

it('does not call the external geocoder when the integration is disabled', function () {
    config()->set('services.geocoding.enabled', false);
    Http::fake();

    expect(app(GeocodingService::class)->search('Matriz'))->toBeNull();

    Http::assertNothingSent();
});
