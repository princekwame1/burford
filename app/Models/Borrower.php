<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrower extends Model
{
    protected $fillable = ['code', 'title', 'full_name', 'father_name', 'mother_name', 'date_of_birth', 'gender',
        'marital_status', 'nationality', 'ghana_card', 'residential_address', 'digital_address', 'city', 'district',
        'phone', 'email', 'photo_path', 'occupation', 'employer_name', 'business_nature', 'business_registration_no',
        'years_in_operation', 'monthly_income', 'business_location', 'business_address', 'business_contact_person',
        'business_phone', 'branch_id'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'monthly_income' => 'decimal:2'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function displayName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->full_name);
    }
}
