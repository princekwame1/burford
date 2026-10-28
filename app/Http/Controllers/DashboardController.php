<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\SusuCollection;
use App\Services\Metrics;
use App\Services\TextTango;

class DashboardController extends Controller
{
    public function __invoke(Metrics $metrics)
    {
        $user = auth()->user();
        $collectorId = $this->ownCollectorId();
        $showCredit = $user->hasRole('admin', 'manager', 'loan_officer');
        $showSusu = $user->hasRole('admin', 'manager', 'collector');

        $data = [
            'showCredit' => $showCredit,
            'showSusu' => $showSusu,
            'susu' => $showSusu ? $metrics->susu(collectorId: $collectorId) : null,
            'trend' => $showSusu ? $metrics->collectionTrend(60, $collectorId) : null,
            'portfolio' => $showSusu ? $metrics->customerPortfolio(8, $collectorId) : collect(),
            'recentCollections' => $showSusu ? SusuCollection::with('customer', 'collector')
                ->when($collectorId, fn ($q) => $q->where('collector_id', $collectorId))
                ->latest('date')->latest('id')->limit(6)->get() : collect(),
            'credit' => $showCredit ? $metrics->credit() : null,
            'statusBreakdown' => $showCredit ? $metrics->statusBreakdown() : collect(),
            'disbursements' => $showCredit ? $metrics->disbursementTrend(6) : null,
            'overdueLoans' => $showCredit ? Loan::with('borrower')->open()->get()
                ->filter(fn (Loan $l) => $l->arrears() > 0.009)
                ->sortByDesc(fn (Loan $l) => $l->daysOverdue())->take(6) : collect(),
            'pendingApplications' => $showCredit ? LoanApplication::with('borrower')->where('status', 'Pending')->latest()->limit(5)->get() : collect(),
            'smsStats' => $user->hasRole('admin', 'manager') ? [
                'sent' => SmsMessage::where('status', 'sent')->whereDate('created_at', '>=', today()->subDays(30))->count(),
                'failed' => SmsMessage::where('status', 'failed')->whereDate('created_at', '>=', today()->subDays(30))->count(),
            ] : null,
        ];

        // SMS credit card: managers and admins only. Cached so TextTango isn't called on every page view.
        if ($data['smsStats']) {
            $client = new TextTango;
            $data['sms'] = [
                'configured' => $client->isConfigured(),
                'wallet' => $client->isConfigured() ? cache()->remember('texttango.wallet', 300, fn () => $client->wallet()) : null,
                'topUps' => $client->isConfigured() ? cache()->remember('texttango.topups', 300, fn () => $client->topUps()) : [],
                'lowAt' => (float) Setting::get('sms_low_balance', 20),
                'topUpUrl' => TextTango::topUpUrl(),
            ];
        }

        return view('dashboard', $data);
    }
}
