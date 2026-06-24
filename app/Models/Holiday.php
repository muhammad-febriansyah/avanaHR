<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_national' => 'boolean',
        ];
    }

    /**
     * Store and expose the holiday date as a pure Y-m-d string so it matches
     * a DATE column exactly (the default date cast appends a time component,
     * which breaks unique-date validation on SQLite).
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(WorkCalendar::class, 'calendar_id');
    }
}
