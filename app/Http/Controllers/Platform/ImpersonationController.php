<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lets a platform super-admin temporarily sign in as a tenant admin user for
 * support/debugging. The original super-admin id is stashed in the session so
 * the impersonation can be reversed.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless((bool) ($request->user()?->getAttributes()['is_super_admin'] ?? false), 403);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $target = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['tenant-admin', 'hr-admin']))
            ->first()
            ?? User::query()->where('tenant_id', $tenant->id)->first();

        if ($target === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Tenant ini belum punya pengguna.']);

            return back();
        }

        $request->session()->put('impersonator_id', $request->user()->id);
        Auth::login($target);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if ($impersonatorId !== null && ($original = User::find($impersonatorId)) !== null) {
            Auth::login($original);
        }

        return redirect()->route('platform.tenants.index');
    }
}
