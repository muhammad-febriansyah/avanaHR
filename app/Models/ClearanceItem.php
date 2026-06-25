<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(EmployeeMovement::class, 'employee_movement_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'done' => 'Selesai',
            'waived' => 'Dikecualikan',
            default => 'Menunggu',
        };
    }

    /**
     * Default exit-clearance checklist generated for resign/terminate movements.
     *
     * @return list<array{category: string, label: string}>
     */
    public static function defaultChecklist(): array
    {
        return [
            ['category' => 'hr', 'label' => 'Exit interview & serah terima HR'],
            ['category' => 'finance', 'label' => 'Pelunasan kasbon & settlement finance'],
            ['category' => 'it', 'label' => 'Penonaktifan akun & email (IT)'],
            ['category' => 'asset', 'label' => 'Pengembalian aset perusahaan'],
            ['category' => 'legal', 'label' => 'Dokumen & kewajiban legal'],
        ];
    }
}
