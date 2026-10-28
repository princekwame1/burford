<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Loan extends Model
{
    public const FREQUENCIES = ['Daily', 'Weekly', 'Bi-weekly', 'Monthly'];

    public const STATUSES = ['Active', 'Overdue', 'Defaulted', 'Closed'];

    protected $fillable = ['loan_no', 'borrower_id', 'loan_application_id', 'branch_id', 'officer_id',
        'disbursement_date', 'principal', 'interest_rate', 'term', 'frequency', 'first_due_date', 'due_date',
        'interest_amount', 'total_repayment', 'installment', 'amount_repaid', 'status', 'disbursement_method', 'notes'];

    protected function casts(): array
    {
        return [
            'disbursement_date' => 'date',
            'first_due_date' => 'date',
            'due_date' => 'date',
            'principal' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_repayment' => 'decimal:2',
            'installment' => 'decimal:2',
            'amount_repaid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Workbook rule: Interest = Loan Amount × Interest Rate × Term; Total = Loan + Interest.
        static::saving(function (Loan $loan) {
            $loan->interest_amount = round((float) $loan->principal * (float) $loan->interest_rate / 100 * (int) $loan->term, 2);
            $loan->total_repayment = (float) $loan->principal + (float) $loan->interest_amount;
            $loan->installment = $loan->term > 0 ? round($loan->total_repayment / $loan->term, 2) : $loan->total_repayment;
            $loan->first_due_date = static::addPeriods($loan->disbursement_date, $loan->frequency, 1);
            $loan->due_date = static::addPeriods($loan->disbursement_date, $loan->frequency, (int) $loan->term);
        });
    }

    public static function addPeriods(CarbonInterface|string $date, string $frequency, int $periods): Carbon
    {
        $date = Carbon::parse($date)->startOfDay();

        return match ($frequency) {
            'Daily' => $date->addDays($periods),
            'Bi-weekly' => $date->addWeeks(2 * $periods),
            'Monthly' => $date->addMonthsNoOverflow($periods),
            default => $date->addWeeks($periods),
        };
    }

    public static function periodLabel(string $frequency): string
    {
        return match ($frequency) {
            'Daily' => 'Day',
            'Bi-weekly' => 'Fortnight',
            'Monthly' => 'Month',
            default => 'Week',
        };
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class)->orderBy('date');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', ['Active', 'Overdue', 'Defaulted']);
    }

    public function outstanding(): float
    {
        return max((float) $this->total_repayment - (float) $this->amount_repaid, 0);
    }

    /** Amount that should have been paid by $asOf according to the schedule. */
    public function expectedBy(?CarbonInterface $asOf = null): float
    {
        $asOf ??= today();
        $due = 0;
        for ($i = 1; $i <= $this->term; $i++) {
            if (static::addPeriods($this->disbursement_date, $this->frequency, $i)->lte($asOf)) {
                $due++;
            }
        }

        return $due >= $this->term ? (float) $this->total_repayment : round($due * (float) $this->installment, 2);
    }

    public function arrears(?CarbonInterface $asOf = null): float
    {
        return max($this->expectedBy($asOf) - (float) $this->amount_repaid, 0);
    }

    /** Days since the oldest installment that is still unpaid fell due. */
    public function daysOverdue(?CarbonInterface $asOf = null): int
    {
        $asOf ??= today();
        foreach ($this->schedule() as $row) {
            if ($row['balance'] > 0.009 && $row['due_date']->lt($asOf)) {
                return (int) $row['due_date']->diffInDays($asOf);
            }
        }

        return 0;
    }

    /** Installment schedule with payments allocated oldest-first (the WEEKLY REPAYMENT sheet). */
    public function schedule(): Collection
    {
        $remaining = (float) $this->amount_repaid;
        $rows = collect();
        for ($i = 1; $i <= $this->term; $i++) {
            $amount = $i === (int) $this->term
                ? round((float) $this->total_repayment - (float) $this->installment * ($this->term - 1), 2)
                : (float) $this->installment;
            $paid = min($amount, $remaining);
            $remaining -= $paid;
            $dueDate = static::addPeriods($this->disbursement_date, $this->frequency, $i);
            $balance = round($amount - $paid, 2);
            $rows->push([
                'period' => $i,
                'due_date' => $dueDate,
                'amount' => $amount,
                'paid' => round($paid, 2),
                'balance' => $balance,
                'status' => $balance <= 0.009 ? 'Paid' : ($paid > 0 ? 'Partial' : ($dueDate->lt(today()) ? 'Missed' : 'Upcoming')),
            ]);
        }

        return $rows;
    }

    public function nextInstallment(): ?array
    {
        return $this->schedule()->first(fn ($r) => $r['balance'] > 0.009);
    }

    public function recalculate(): void
    {
        $this->amount_repaid = $this->repayments()->sum('amount');
        $this->refreshStatus(false);
        $this->save();
    }

    public function refreshStatus(bool $save = true): void
    {
        if ($this->outstanding() <= 0.009) {
            $this->status = 'Closed';
        } elseif ($this->status !== 'Defaulted') {
            $this->status = $this->arrears() > 0.009 ? 'Overdue' : 'Active';
        }

        if ($save) {
            $this->saveQuietly();
        }
    }

    public function repaymentRate(): float
    {
        return (float) $this->total_repayment > 0 ? (float) $this->amount_repaid / (float) $this->total_repayment : 0;
    }
}
