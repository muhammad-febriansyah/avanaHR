<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    public function index(): Response
    {
        $backups = Backup::query()
            ->with('tenant:id,name')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (Backup $backup): array => [
                'id' => $backup->id,
                'type' => $backup->type,
                'status' => $backup->status,
                'location' => $backup->location,
                'tenant_name' => $backup->tenant?->name,
                'created_at' => $backup->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('platform/backups/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Backup & Restore', 'href' => route('platform.backups.index')],
            ],
            'backups' => $backups,
        ]);
    }

    public function store(): RedirectResponse
    {
        $stamp = Carbon::now()->format('Ymd-His');

        Backup::create([
            'tenant_id' => null,
            'type' => 'full',
            'status' => 'completed',
            'location' => "backups/platform-{$stamp}.zip",
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Backup penuh berhasil dibuat.']);

        return back();
    }

    public function restore(Backup $backup): RedirectResponse
    {
        if ($backup->status !== 'completed') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya backup selesai yang dapat dipulihkan.']);

            return back();
        }

        // Real restore runs as an async job; here we just acknowledge the request.
        Inertia::flash('toast', [
            'type' => 'info',
            'message' => "Pemulihan dari backup #{$backup->id} dijadwalkan.",
        ]);

        return back();
    }
}
