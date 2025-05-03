<?php

namespace App\Interfaces;

interface GeocodingServiceInterface
{
    public function search(string $query): ?array;
}
