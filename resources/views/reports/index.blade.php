<x-layouts.app title="Reports">
    <x-page-header title="Reports" subtitle="Management reporting across collections and the loan book." />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['reports.susu', 'coins', 'Susu collections report', 'Expected vs collected by day, collector performance against target, payment-method mix and the full customer portfolio.', 'brand'],
            ['reports.portfolio', 'pie', 'Loan portfolio report', 'Status breakdown, branch performance, PAR30, arrears ageing and the 12-month disbursement trend.', 'blue'],
            ['credit.weekly', 'grid', 'Weekly repayment sheet', 'One row per loan, one column per week — the classic repayment card, coloured by status.', 'violet'],
        ] as [$route, $icon, $title, $text, $tone])
            <a href="{{ route($route) }}" class="card group p-6 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-900/5">
                <span class="grid size-11 place-items-center rounded-xl {{ ['brand' => 'bg-brand-50 text-brand-700', 'blue' => 'bg-sky-50 text-sky-700', 'violet' => 'bg-violet-50 text-violet-700'][$tone] }} dark:bg-white/5"><x-icon :name="$icon" /></span>
                <p class="mt-4 font-display font-bold text-ink-900 dark:text-white">{{ $title }}</p>
                <p class="mt-1 text-sm text-ink-500">{{ $text }}</p>
                <p class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 dark:text-brand-300">Open <x-icon name="chevron-right" class="size-4 transition group-hover:translate-x-0.5" /></p>
            </a>
        @endforeach
    </div>
    <div class="card mt-6">
        <div class="card-header"><h3 class="card-title">Data exports (CSV, opens in Excel)</h3></div>
        <div class="card-body flex flex-wrap gap-2">
            @foreach (['customers' => 'Customer master', 'collections' => 'Daily collections', 'withdrawals' => 'Withdrawals', 'loans' => 'Loan data', 'repayments' => 'Loan repayments'] as $k => $l)
                <a href="{{ route('reports.export', $k) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> {{ $l }}</a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
