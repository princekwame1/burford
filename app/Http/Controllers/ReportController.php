<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\SusuCollection;
use App\Models\SusuCustomer;
use App\Models\SusuWithdrawal;
use App\Services\Metrics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function susu(Request $request, Metrics $metrics)
    {
        [$from, $to] = $this->range($request);

        return view('reports.susu', [
            'from' => $from, 'to' => $to,
            'summary' => $metrics->susu($from, $to),
            'collectors' => $metrics->collectorPerformance($from, $to),
            'portfolio' => $metrics->customerPortfolio(100),
            'daily' => SusuCollection::whereBetween('date', [$from, $to])
                ->selectRaw('date, SUM(expected) expected, SUM(collected) collected, SUM(outstanding) outstanding, COUNT(*) receipts')
                ->groupBy('date')->orderBy('date')->get(),
            'methods' => SusuCollection::whereBetween('date', [$from, $to])
                ->selectRaw('payment_method, SUM(collected) total')->groupBy('payment_method')->pluck('total', 'payment_method'),
        ]);
    }

    public function portfolio(Metrics $metrics)
    {
        $loans = Loan::with('borrower')->open()->get();

        // Ageing buckets on outstanding balance by days overdue.
        $buckets = ['Current' => [0, 0], '1–30 days' => [1, 30], '31–60 days' => [31, 60], '61–90 days' => [61, 90], '90+ days' => [91, PHP_INT_MAX]];
        $ageing = collect($buckets)->map(function ($range) use ($loans) {
            $set = $loans->filter(fn (Loan $l) => ($d = $l->daysOverdue()) >= $range[0] && $d <= $range[1]);

            return ['count' => $set->count(), 'outstanding' => $set->sum(fn (Loan $l) => $l->outstanding())];
        });

        return view('reports.portfolio', [
            'credit' => $metrics->credit(),
            'status' => $metrics->statusBreakdown(),
            'branches' => $metrics->branchPerformance(),
            'trend' => $metrics->disbursementTrend(12),
            'ageing' => $ageing,
            'atRisk' => $loans->filter(fn (Loan $l) => $l->daysOverdue() >= 30)->sortByDesc(fn ($l) => $l->daysOverdue()),
        ]);
    }

    /** The "WEEKLY REPAYMENT" sheet: one row per loan, one column per period. */
    public function weekly(Request $request)
    {
        $loans = Loan::with('borrower')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->open())
            ->orderBy('loan_no')->get();
        $maxTerm = max((int) $loans->max('term'), 1);

        return view('reports.weekly', ['loans' => $loans, 'maxTerm' => min($maxTerm, 52)]);
    }

    public function export(string $dataset): StreamedResponse
    {
        [$headers, $rows] = match ($dataset) {
            'customers' => [
                ['Customer ID', 'Name', 'Phone', 'Address', 'Collector', 'Plan', 'Contribution', 'Frequency', 'Start Date', 'Status', 'Balance'],
                SusuCustomer::with('collector')->orderBy('code')->get()->map(fn ($c) => [
                    $c->code, $c->name, $c->phone, $c->address, $c->collector?->name, $c->plan, $c->contribution,
                    $c->frequency, $c->start_date?->toDateString(), $c->status, round($c->balance(), 2)]),
            ],
            'collections' => [
                ['Receipt No.', 'Date', 'Customer ID', 'Customer Name', 'Collector', 'Expected', 'Collected', 'Outstanding', 'Payment Method', 'Status', 'Verified By', 'Deposit Ref.', 'Notes'],
                SusuCollection::with('customer', 'collector')->orderBy('date')->get()->map(fn ($c) => [
                    $c->receipt_no, $c->date->toDateString(), $c->customer?->code, $c->customer?->name, $c->collector?->name,
                    $c->expected, $c->collected, $c->outstanding, $c->payment_method, $c->status, $c->verified_by, $c->deposit_ref, $c->notes]),
            ],
            'withdrawals' => [
                ['Withdrawal No.', 'Date', 'Customer ID', 'Customer Name', 'Amount', 'Commission', 'Type', 'Approved By', 'Payment Method', 'Balance After', 'Notes'],
                SusuWithdrawal::with('customer')->orderBy('date')->get()->map(fn ($w) => [
                    $w->withdrawal_no, $w->date->toDateString(), $w->customer?->code, $w->customer?->name, $w->amount, $w->commission,
                    $w->type, $w->approved_by, $w->payment_method, $w->balance_after, $w->notes]),
            ],
            'loans' => [
                ['Loan ID', 'Borrower Name', 'Branch', 'Loan Officer', 'Disbursement Date', 'Loan Amount (GHC)', 'Interest Rate (%)', 'Term', 'Frequency', 'Due Date', 'Amount Repaid (GHC)', 'Interest Amount', 'Total Repayment', 'Outstanding Balance (GHC)', 'Days Overdue', 'Status'],
                Loan::with('borrower', 'branch', 'officer')->orderBy('loan_no')->get()->map(fn ($l) => [
                    $l->loan_no, $l->borrower?->full_name, $l->branch?->name, $l->officer?->name, $l->disbursement_date->toDateString(),
                    $l->principal, $l->interest_rate, $l->term, $l->frequency, $l->due_date?->toDateString(), $l->amount_repaid,
                    $l->interest_amount, $l->total_repayment, round($l->outstanding(), 2), $l->daysOverdue(), $l->status]),
            ],
            'repayments' => [
                ['Receipt No.', 'Date', 'Loan ID', 'Borrower', 'Period', 'Amount', 'Method', 'Reference', 'Received By'],
                LoanRepayment::with('loan.borrower', 'receiver')->orderBy('date')->get()->map(fn ($r) => [
                    $r->receipt_no, $r->date->toDateString(), $r->loan?->loan_no, $r->loan?->borrower?->full_name, $r->period_no,
                    $r->amount, $r->payment_method, $r->reference, $r->receiver?->name]),
            ],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "mkduapa-{$dataset}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function range(Request $request): array
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->startOfDay()];
    }
}
