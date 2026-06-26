<?php

namespace App\Jobs;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Restores the platform database from a backup .sql dump. Destructive — runs
 * off the web request as a queued job. MySQL only; other drivers are a no-op.
 */
class RestorePlatformBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $backupId) {}

    public function handle(): void
    {
        $backup = Backup::query()->withoutGlobalScopes()->find($this->backupId);

        if ($backup === null || ! Storage::disk('local')->exists($backup->location)) {
            return;
        }

        if (config('database.default') !== 'mysql') {
            return; // restore only implemented for MySQL
        }

        $config = config('database.connections.mysql');
        $path = Storage::disk('local')->path($backup->location);

        $process = Process::fromShellCommandline(
            'mysql --host=${:HOST} --port=${:PORT} --user=${:USER} ${:DB} < ${:FILE}',
            null,
            [
                'MYSQL_PWD' => (string) ($config['password'] ?? ''),
                'HOST' => $config['host'] ?? '127.0.0.1',
                'PORT' => (string) ($config['port'] ?? '3306'),
                'USER' => $config['username'] ?? 'root',
                'DB' => $config['database'],
                'FILE' => $path,
            ],
        );
        $process->setTimeout(600);
        $process->run();
    }
}
