<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'birth_date' => 'date',
            'join_date' => 'date',
            'resign_date' => 'date',
        ];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(EmployeeEmployment::class);
    }

    /**
     * Latest employment effective on or before the given date (defaults to today).
     */
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(EmployeeEmployment::class)
            ->whereDate('effective_date', '<=', now())
            ->orderByDesc('effective_date')
            ->orderByDesc('id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function taxProfiles(): HasMany
    {
        return $this->hasMany(EmployeeTaxProfile::class);
    }

    public function bpjsProfiles(): HasMany
    {
        return $this->hasMany(EmployeeBpjsProfile::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(EmployeeLifecycleEvent::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EmployeeMovement::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }
}
