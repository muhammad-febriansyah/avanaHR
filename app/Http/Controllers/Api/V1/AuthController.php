<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

/**
 * JWT authentication for the Flutter mobile app (ESS/MSS).
 * Web stays on Fortify sessions; this guard is stateless and mobile-only.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $token = Auth::guard('api')->attempt($credentials);

        if (! $token) {
            return response()->json([
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        $user = Auth::guard('api')->user();

        if ($user->status !== 'active') {
            Auth::guard('api')->logout();

            return response()->json([
                'message' => 'Akun tidak aktif. Hubungi admin HR.',
            ], 403);
        }

        // Bind tenant context so role names resolve in the login response
        // (the SetCurrentTenant middleware only covers authenticated routes).
        $tenant = $user->tenant_id ? Tenant::find($user->tenant_id) : null;
        app(CurrentTenant::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);

        return $this->tokenResponse($token);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(Auth::guard('api')->user()->loadMissing('employee')),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(Auth::guard('api')->refresh());
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => new UserResource(Auth::guard('api')->user()->loadMissing('employee')),
        ]);
    }
}
