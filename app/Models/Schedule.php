<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            // Pin to Y-m-d so updateOrCreate matches identically across drivers
            // (SQLite keeps the time component a plain 'date' cast would add).
            'date' => 'date:Y-m-d',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
