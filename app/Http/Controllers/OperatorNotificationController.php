<?php

namespace App\Http\Controllers;

use App\Services\OperatorNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperatorNotificationController extends Controller
{
    public function index(Request $request, OperatorNotificationService $notifications): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->visibleTo($request->user())->latest()->paginate(20),
        ]);
    }

    public function summary(Request $request, OperatorNotificationService $notifications): JsonResponse
    {
        return response()->json($notifications->summary($request->user()))
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function markRead(Request $request, int $notification, OperatorNotificationService $notifications): JsonResponse
    {
        $notifications->markRead($request->user(), $notification);

        return response()->json($notifications->summary($request->user()))
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
