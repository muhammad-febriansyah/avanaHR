<?php

namespace App\Http\Controllers;

use App\Repositories\Organization\OrganizationRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationRepository $organization) {}

    public function structure(Request $request): Response
    {
        abort_unless($request->user()->can('employee.view'), 403);

        return Inertia::render('organization/structure', [
            'tree' => $this->organization->tree(),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Struktur Organisasi', 'href' => route('organization.structure')],
            ],
        ]);
    }
}
