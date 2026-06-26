<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\CreatePlatformBackupAction;
use App\Http\Controllers\Controller;
use App\Jobs\RestorePlatformBackup;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
                'size_bytes' => $backup->size_bytes,
                'error' => $backup->error,
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

    public function store(CreatePlatformBackupAction $action): RedirectResponse
    {
        $backup = $action->execute();

        $message = $backup->status === 'completed'
            ? 'Backup database berhasil dibuat.'
            : 'Backup gagal: '.$backup->error;

        Inertia::flash('toast', [
            'type' => $backup->status === 'completed' ? 'success' : 'error',
            'message' => $message,
        ]);

        return back();
    }

    /**
     * Download the backup file so it can be archived or restored manually.
     */
    public function download(Backup $backup): BinaryFileResponse
    {
        abort_unless($backup->status === 'completed' && Storage::disk('local')->exists($backup->location), 404);

        return response()->download(Storage::disk('local')->path($backup->location));
    }

    public function restore(Backup $backup): RedirectResponse
    {
        if ($backup->status !== 'completed' || ! Storage::disk('local')->exists($backup->location)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Berkas backup tidak ditemukan atau belum selesai.']);

            return back();
        }

        // Restore is destructive + slow → run off the request via a queued job.
        RestorePlatformBackup::dispatch($backup->id);

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => "Pemulihan dari backup #{$backup->id} dijadwalkan (berjalan di latar belakang).",
        ]);

        return back();
    }
}
