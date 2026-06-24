<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsParameter extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'kes_rate_employee' => 'decimal:4',
            'kes_rate_employer' => 'decimal:4',
            'kes_cap' => 'integer',
            'tk_rates' => 'array',
        ];
    }
}
