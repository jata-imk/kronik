<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeocodingRequest;
use App\Interfaces\GeocodingServiceInterface;
use Illuminate\Http\JsonResponse;

class GeocodingController extends Controller
{
    public function __construct(
        protected GeocodingServiceInterface $geocodingService
    ) {}

    public function search(GeocodingRequest $request): JsonResponse
    {
        $query = $request->validated()['query'];
        $data = $this->geocodingService->search($query);

        if (!$data) {
            return response()->json(['error' => 'No se encontraron resultados'], 404);
        }

        return response()->json($data);
    }
}
