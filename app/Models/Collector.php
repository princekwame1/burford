<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collector extends Model
{
    protected $fillable = ['code', 'user_id', 'branch_id', 'name', 'phone', 'territory', 'start_date', 'status',
        'daily_target', 'cash_limit', 'supervisor'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'daily_target' => 'decimal:2', 'cash_limit' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(SusuCustomer::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(SusuCollection::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }
}
