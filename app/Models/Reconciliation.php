<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reconciliation extends Model
{
    protected $fillable = ['date', 'collector_id', 'system_collections', 'cash_counted', 'mobile_money', 'total_counted',
        'variance', 'deposit_amount', 'deposit_ref', 'status', 'notes', 'reconciled_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'system_collections' => 'decimal:2', 'cash_counted' => 'decimal:2',
            'mobile_money' => 'decimal:2', 'total_counted' => 'decimal:2', 'variance' => 'decimal:2',
            'deposit_amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        // System collections = SUMIFS(collected, date, collector); variance = counted − system.
        static::saving(function (Reconciliation $r) {
            $r->system_collections = SusuCollection::query()
                ->whereDate('date', $r->date)
                ->where('collector_id', $r->collector_id)
                ->sum('collected');
            $r->total_counted = (float) $r->cash_counted + (float) $r->mobile_money;
            $r->variance = (float) $r->total_counted - (float) $r->system_collections;
            if (! (float) $r->deposit_amount) {
                $r->deposit_amount = $r->total_counted;
            }
        });
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
