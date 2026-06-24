<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()->can('setting.manage'), 403);

        return Inertia::render('permissions/index', [
            'permissionGroups' => Permissions::grouped(),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Permission', 'href' => route('permissions.index')],
            ],
        ]);
    }
}
