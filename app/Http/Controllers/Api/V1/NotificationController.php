<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        return NotificationResource::collection($notifications)
            ->additional(['meta' => [
                'unread' => Notification::query()
                    ->where('user_id', $request->user()->id)
                    ->whereNull('read_at')
                    ->count(),
            ]])
            ->response();
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        // IDOR guard: only the owner may mark a notification read.
        if ($notification->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Notifikasi ini bukan milik Anda.');
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['data' => new NotificationResource($notification)]);
    }

    public function readAll(Request $request): JsonResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
