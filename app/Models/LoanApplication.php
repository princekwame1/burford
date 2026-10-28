<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    public const PURPOSES = ['Working Capital', 'Stock Purchase', 'Equipment', 'Expansion', 'Agriculture', 'Education',
        'Household/Personal', 'Emergency', 'Other'];

    protected $fillable = ['application_no', 'application_date', 'borrower_id', 'branch_id', 'officer_id',
        'amount_requested', 'purpose', 'purpose_other', 'preferred_term', 'repayment_frequency',
        'preferred_disbursement_date', 'proposed_repayment_start', 'family_members', 'existing_obligations',
        'guarantor_name', 'guarantor_phone', 'guarantor_relationship', 'guarantor_address', 'declaration_accepted',
        'status', 'approved_amount', 'interest_rate', 'approved_term', 'approved_frequency', 'approved_by',
        'decided_at', 'decision_notes'];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'preferred_disbursement_date' => 'date',
            'proposed_repayment_start' => 'date',
            'decided_at' => 'datetime',
            'family_members' => 'array',
            'existing_obligations' => 'array',
            'declaration_accepted' => 'boolean',
            'amount_requested' => 'decimal:2',
            'approved_amount' => 'decimal:2',
        ];
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class);
    }

    /** Interest / service charge and total payable for the approved terms (flat rate per period). */
    public function approvedTotals(): array
    {
        $principal = (float) $this->approved_amount;
        $interest = round($principal * (float) $this->interest_rate / 100 * (int) $this->approved_term, 2);

        return ['interest' => $interest, 'total' => $principal + $interest];
    }

    public function totalObligations(): float
    {
        return (float) collect($this->existing_obligations ?? [])->sum(fn ($o) => (float) ($o['monthly_repayment'] ?? 0));
    }
}
