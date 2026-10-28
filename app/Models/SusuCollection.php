<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SusuCollection extends Model
{
    protected $fillable = ['receipt_no', 'date', 'susu_customer_id', 'collector_id', 'expected', 'collected',
        'outstanding', 'payment_method', 'status', 'verified_by', 'deposit_ref', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'expected' => 'decimal:2', 'collected' => 'decimal:2', 'outstanding' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        // Same rules as the workbook: Outstanding = MAX(Expected − Collected, 0); status Paid/Partial/Unpaid.
        static::saving(function (SusuCollection $c) {
            $c->outstanding = max((float) $c->expected - (float) $c->collected, 0);
            $c->status = (float) $c->outstanding == 0.0 ? 'Paid' : ((float) $c->collected > 0 ? 'Partial' : 'Unpaid');
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SusuCustomer::class, 'susu_customer_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
