<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\Reconciliation;
use App\Models\SusuCollection;
use App\Models\SusuCustomer;
use App\Models\SusuWithdrawal;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * KPI calculations mirroring the "Executive Dashboard" (Susu) and "Dashboard"/"Summary Tables" (Micro Credit) sheets.
 */
class Metrics
{
    public function susu(?Carbon $from = null, ?Carbon $to = null, ?int $collectorId = null): array
    {
        $c = SusuCollection::query()
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId));

        $expected = (float) (clone $c)->sum('expected');
        $collected = (float) (clone $c)->sum('collected');

        $withdrawals = SusuWithdrawal::query()
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->when($collectorId, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('collector_id', $collectorId)));

        $totalSavings = (float) SusuCustomer::when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->sum('opening_balance')
            + (float) SusuCollection::when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->sum('collected')
            - (float) SusuWithdrawal::when($collectorId, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('collector_id', $collectorId)))->sum('amount')
            - (float) SusuWithdrawal::when($collectorId, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('collector_id', $collectorId)))->sum('commission');

        return [
            'expected' => $expected,
            'collected' => $collected,
            'outstanding' => (float) (clone $c)->sum('outstanding'),
            'rate' => $expected > 0 ? $collected / $expected : 0,
            'customers' => SusuCustomer::when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->count(),
            'active_customers' => SusuCustomer::where('status', 'Active')->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->count(),
            'active_collectors' => Collector::where('status', 'Active')->count(),
            'withdrawals' => (float) (clone $withdrawals)->sum('amount'),
            'commission' => (float) (clone $withdrawals)->sum('commission'),
            'paid_count' => (clone $c)->where('status', 'Paid')->count(),
            'partial_count' => (clone $c)->whereIn('status', ['Partial', 'Unpaid'])->count(),
            'unreconciled_variance' => (float) Reconciliation::where('status', '!=', 'Reconciled')
                ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->sum('variance'),
            'open_reconciliations' => Reconciliation::where('status', '!=', 'Reconciled')->count(),
            'savings_balance' => $totalSavings,
            'today' => (float) SusuCollection::whereDate('date', today())
                ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))->sum('collected'),
        ];
    }

    public function credit(): array
    {
        $loans = Loan::with('repayments')->get();
        $open = $loans->whereIn('status', ['Active', 'Overdue', 'Defaulted']);
        $outstanding = $open->sum(fn (Loan $l) => $l->outstanding());
        $atRisk = $open->filter(fn (Loan $l) => $l->daysOverdue() >= 30)->sum(fn (Loan $l) => $l->outstanding());
        $disbursed = (float) $loans->sum('principal');

        return [
            'disbursed' => $disbursed,
            'outstanding' => $outstanding,
            'active_borrowers' => $open->pluck('borrower_id')->unique()->count(),
            'total_loans' => $loans->count(),
            'par30' => $outstanding > 0 ? $atRisk / $outstanding : 0,
            'at_risk' => $atRisk,
            // Workbook definition: cumulative repaid ÷ cumulative disbursed.
            'repayment_rate' => $disbursed > 0 ? (float) $loans->sum('amount_repaid') / $disbursed : 0,
            'avg_loan' => $loans->count() ? $disbursed / $loans->count() : 0,
            'default_rate' => $loans->count() ? $loans->where('status', 'Defaulted')->count() / $loans->count() : 0,
            'interest_expected' => (float) $loans->sum('interest_amount'),
            'repaid' => (float) $loans->sum('amount_repaid'),
            'arrears' => $open->sum(fn (Loan $l) => $l->arrears()),
            'overdue_count' => $open->filter(fn (Loan $l) => $l->arrears() > 0.009)->count(),
            'pending_applications' => LoanApplication::where('status', 'Pending')->count(),
            'approved_applications' => LoanApplication::where('status', 'Approved')->count(),
            'repaid_today' => (float) LoanRepayment::whereDate('date', today())->sum('amount'),
        ];
    }

    /** Daily expected vs collected for the last $days days (dashboard trend chart). */
    public function collectionTrend(int $days = 30, ?int $collectorId = null): array
    {
        $from = today()->subDays($days - 1);
        $rows = SusuCollection::query()
            ->whereDate('date', '>=', $from)
            ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))
            ->get()
            ->groupBy(fn ($c) => $c->date->toDateString());

        $labels = $expected = $collected = [];
        foreach (CarbonPeriod::create($from, today()) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $expected[] = round((float) ($rows[$key] ?? collect())->sum('expected'), 2);
            $collected[] = round((float) ($rows[$key] ?? collect())->sum('collected'), 2);
        }

        return compact('labels', 'expected', 'collected');
    }

    /** "Customer Portfolio" table: expected, collected, outstanding and rate per customer. */
    public function customerPortfolio(int $limit = 10, ?int $collectorId = null): Collection
    {
        return SusuCustomer::query()
            ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))
            ->withSum('collections as expected', 'expected')
            ->withSum('collections as collected', 'collected')
            ->orderByDesc('expected')
            ->limit($limit)
            ->get()
            ->map(function ($c) {
                $c->outstanding_total = (float) $c->expected - (float) $c->collected;
                $c->rate = (float) $c->expected > 0 ? (float) $c->collected / (float) $c->expected : 0;

                return $c;
            });
    }

    /** Loan status breakdown: count and outstanding per status. */
    public function statusBreakdown(): Collection
    {
        $loans = Loan::all();

        return collect(Loan::STATUSES)->map(fn ($s) => [
            'status' => $s,
            'count' => $loans->where('status', $s)->count(),
            'outstanding' => $loans->where('status', $s)->sum(fn (Loan $l) => $l->outstanding()),
        ]);
    }

    /** Branch performance: disbursed, outstanding, active loans, average loan. */
    public function branchPerformance(): Collection
    {
        return Branch::with('loans')->get()->map(function (Branch $b) {
            $loans = $b->loans;

            return [
                'branch' => $b->name,
                'disbursed' => (float) $loans->sum('principal'),
                'outstanding' => $loans->whereIn('status', ['Active', 'Overdue', 'Defaulted'])->sum(fn (Loan $l) => $l->outstanding()),
                'active' => $loans->whereIn('status', ['Active', 'Overdue'])->count(),
                'avg' => $loans->count() ? (float) $loans->sum('principal') / $loans->count() : 0,
            ];
        });
    }

    /** Monthly disbursement trend for the last $months months. */
    public function disbursementTrend(int $months = 6): array
    {
        $labels = $amounts = $counts = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = today()->startOfMonth()->subMonthsNoOverflow($i);
            $end = $start->copy()->endOfMonth();
            $q = Loan::whereBetween('disbursement_date', [$start, $end]);
            $labels[] = $start->format('M Y');
            $amounts[] = round((float) (clone $q)->sum('principal'), 2);
            $counts[] = (clone $q)->count();
        }

        return compact('labels', 'amounts', 'counts');
    }

    public function collectorPerformance(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= today()->startOfMonth();
        $to ??= today();

        return Collector::withCount('customers')->get()->map(function (Collector $col) use ($from, $to) {
            $q = $col->collections()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
            $expected = (float) (clone $q)->sum('expected');
            $collected = (float) (clone $q)->sum('collected');
            $days = max($from->diffInDays($to) + 1, 1);

            return [
                'collector' => $col,
                'customers' => $col->customers_count,
                'expected' => $expected,
                'collected' => $collected,
                'rate' => $expected > 0 ? $collected / $expected : 0,
                'target' => (float) $col->daily_target * $days,
                'variance' => (float) $col->reconciliations()->where('status', '!=', 'Reconciled')->sum('variance'),
            ];
        });
    }
}
