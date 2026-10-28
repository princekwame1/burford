<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SusuCustomer extends Model
{
    protected $fillable = ['code', 'name', 'phone', 'address', 'ghana_card', 'collector_id', 'plan', 'contribution',
        'frequency', 'start_date', 'status', 'opening_balance', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'contribution' => 'decimal:2', 'opening_balance' => 'decimal:2'];
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(SusuCollection::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(SusuWithdrawal::class);
    }

    /** Savings balance = opening balance + all collections − withdrawals (incl. commission). */
    public function balance(): float
    {
        return (float) $this->opening_balance
            + (float) $this->collections()->sum('collected')
            - (float) $this->withdrawals()->sum('amount')
            - (float) $this->withdrawals()->sum('commission');
    }

    /** Ledger rows in date order with a running balance, mirroring the "Customer Ledger" sheet. */
    public function ledger(): Collection
    {
        $rows = collect([[
            'date' => $this->start_date ?? $this->created_at,
            'type' => 'Opening Balance',
            'reference' => 'OPEN',
            'credit' => (float) $this->opening_balance,
            'debit' => 0.0,
            'officer' => 'System',
            'notes' => null,
            'sort' => 0,
        ]]);

        foreach ($this->collections()->with('collector')->get() as $c) {
            $rows->push(['date' => $c->date, 'type' => 'Contribution', 'reference' => $c->receipt_no,
                'credit' => (float) $c->collected, 'debit' => 0.0, 'officer' => $c->collector?->name,
                'notes' => $c->status !== 'Paid' ? $c->status.' — short by '.number_format((float) $c->outstanding, 2) : $c->notes,
                'sort' => 1]);
        }

        foreach ($this->withdrawals as $w) {
            $rows->push(['date' => $w->date, 'type' => 'Withdrawal ('.$w->type.')', 'reference' => $w->withdrawal_no,
                'credit' => 0.0, 'debit' => (float) $w->amount + (float) $w->commission, 'officer' => $w->approved_by,
                'notes' => $w->commission > 0 ? 'Incl. commission '.number_format((float) $w->commission, 2) : $w->notes,
                'sort' => 2]);
        }

        $balance = 0.0;

        return $rows->sortBy([
            fn ($a, $b) => $a['date'] <=> $b['date'],
            fn ($a, $b) => $a['sort'] <=> $b['sort'],
        ])->values()->map(function ($row) use (&$balance) {
            $balance += $row['credit'] - $row['debit'];
            $row['balance'] = $balance;

            return $row;
        });
    }
}
