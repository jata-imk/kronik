<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Jetstream\Http\Controllers\Inertia\ApiTokenController as JetstreamApiTokenController;

class ApiTokenController extends JetstreamApiTokenController
{
    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->whereKey($tokenId)->firstOrFail()->delete();

        return back(303);
    }
}
