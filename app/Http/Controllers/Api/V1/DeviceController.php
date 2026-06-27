<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterDeviceRequest;
use App\Models\DeviceRegistration;
use Illuminate\Http\JsonResponse;

/**
 * Registers (or refreshes) a mobile device + FCM token for push notifications.
 * Keyed per user + device so re-installs simply update the token.
 */
class DeviceController extends Controller
{
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $device = DeviceRegistration::updateOrCreate(
            ['user_id' => $request->user()->id, 'device_id' => $data['device_id']],
            [
                'platform' => $data['platform'],
                'fcm_token' => $data['fcm_token'] ?? null,
                'biometric_enabled' => $request->boolean('biometric_enabled'),
                'last_seen' => now(),
            ],
        );

        return response()->json([
            'message' => 'Perangkat terdaftar.',
            'data' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'platform' => $device->platform,
                'biometric_enabled' => (bool) $device->biometric_enabled,
            ],
        ]);
    }
}
