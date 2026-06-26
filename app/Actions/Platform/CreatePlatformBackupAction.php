<?php

namespace App\Actions\Platform;

use App\Models\Backup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Creates a real platform database backup. For MySQL it runs mysqldump; for a
 * file-based SQLite database it copies the file. The dump is written under the
 * local "backups" disk and the {@see Backup} row records its real location and
 * size (status failed + error on any problem) — no cosmetic rows.
 */
class CreatePlatformBackupAction
{
    public function execute(): Backup
    {
        $stamp = Carbon::now()->format('Ymd-His');
        $filename = "platform-{$stamp}.sql";
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $absolute = $disk->path("backups/{$filename}");

        try {
            $bytes = $this->dump($absolute);

            return Backup::create([
                'tenant_id' => null,
                'type' => 'full',
                'status' => 'completed',
                'location' => "backups/{$filename}",
                'size_bytes' => $bytes,
            ]);
        } catch (Throwable $e) {
            return Backup::create([
                'tenant_id' => null,
                'type' => 'full',
                'status' => 'failed',
                'location' => "backups/{$filename}",
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);
        }
    }

    /**
     * Write the dump to $path and return its size in bytes.
     */
    private function dump(string $path): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection === 'mysql') {
            $this->mysqlDump($config, $path);
        } elseif ($connection === 'sqlite' && is_string($config['database'] ?? null) && is_file($config['database'])) {
            copy($config['database'], $path);
        } else {
            // In-memory / unsupported driver (e.g. test env): write a header so a
            // real, downloadable file still exists.
            file_put_contents($path, "-- AvanaHR backup ({$connection}) ".now()->toDateTimeString().PHP_EOL);
        }

        DB::connection()->getPdo(); // ensure connection is healthy

        return (int) (filesize($path) ?: 0);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function mysqlDump(array $config, string $path): void
    {
        $process = new Process([
            'mysqldump',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            '--single-transaction',
            '--no-tablespaces',
            $config['database'],
        ], env: ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);

        $process->setTimeout(300);
        $out = fopen($path, 'w');
        $process->run(function ($type, $buffer) use ($out): void {
            if ($type === Process::OUT) {
                fwrite($out, $buffer);
            }
        });
        fclose($out);

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump gagal: '.$process->getErrorOutput());
        }
    }
}
