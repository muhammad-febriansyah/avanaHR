<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\HrTicket;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Whitelisted data sources for the no-code Report Builder. Each source maps to a
 * tenant-scoped model and an allow-list of selectable columns, so report queries
 * can never touch arbitrary tables or columns.
 */
class ReportSources
{
    /**
     * @return array<string, array{label: string, model: class-string, columns: array<string, string>}>
     */
    public static function all(): array
    {
        return [
            'employees' => [
                'label' => 'Karyawan',
                'model' => Employee::class,
                'columns' => [
                    'employee_no' => 'NIP',
                    'first_name' => 'Nama Depan',
                    'last_name' => 'Nama Belakang',
                    'gender' => 'Jenis Kelamin',
                    'status' => 'Status',
                    'email' => 'Email',
                    'phone' => 'Telepon',
                    'join_date' => 'Tgl Bergabung',
                ],
            ],
            'leave_requests' => [
                'label' => 'Pengajuan Cuti',
                'model' => LeaveRequest::class,
                'columns' => [
                    'start_date' => 'Mulai',
                    'end_date' => 'Selesai',
                    'days' => 'Jumlah Hari',
                    'status' => 'Status',
                    'reason' => 'Alasan',
                ],
            ],
            'hr_tickets' => [
                'label' => 'Tiket HR',
                'model' => HrTicket::class,
                'columns' => [
                    'ticket_no' => 'No. Tiket',
                    'subject' => 'Subjek',
                    'category' => 'Kategori',
                    'priority' => 'Prioritas',
                    'status' => 'Status',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, string>
     */
    public static function columns(?string $source): array
    {
        return self::all()[$source]['columns'] ?? [];
    }

    public static function has(string $source): bool
    {
        return array_key_exists($source, self::all());
    }

    /**
     * @return Builder<Model>
     */
    public static function query(string $source): Builder
    {
        $model = self::all()[$source]['model'];

        return $model::query();
    }
}
