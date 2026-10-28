<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SusuWithdrawal extends Model
{
    protected $fillable = ['withdrawal_no', 'date', 'susu_customer_id', 'amount', 'commission', 'type', 'approved_by',
        'payment_method', 'balance_after', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2', 'commission' => 'decimal:2', 'balance_after' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SusuCustomer::class, 'susu_customer_id');
    }
}
