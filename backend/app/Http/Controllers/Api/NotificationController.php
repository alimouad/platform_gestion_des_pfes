<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $notifications,
            'unread' => $notifications->whereNull('lue_le')->count(),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $notif = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notif->update(['lue_le' => now()]);
        return response()->json(['data' => $notif]);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('lue_le')
            ->update(['lue_le' => now()]);
        return response()->json(['message' => 'ok']);
    }
}
