@php use App\Support\Money; $pct = fn ($v) => number_format($v * 100, 1).'%'; @endphp
<x-layouts.app title="Portfolio report">
    <x-page-header title="Loan portfolio report" :subtitle="'As at '.now()->format('d M Y')" :back="route('reports.index')">
        <button onclick="window.print()" class="btn btn-secondary btn-sm"><x-icon name="printer" class="size-3.5" /> Print</button>
    </x-page-header>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Total disbursed" :value="Money::format($credit['disbursed'])" icon="banknote" tone="brand" />
        <x-stat label="Total outstanding" :value="Money::format($credit['outstanding'])" icon="wallet" tone="blue" />
        <x-stat label="Active borrowers" :value="$credit['active_borrowers']" icon="users" tone="ink" />
        <x-stat label="Total loans" :value="$credit['total_loans']" icon="landmark" tone="ink" />
        <x-stat label="Portfolio at risk (PAR30)" :value="$pct($credit['par30'])" icon="shield" :tone="$credit['par30'] > .05 ? 'rose' : 'brand'" />
        <x-stat label="Repayment rate" :value="$pct($credit['repayment_rate'])" icon="trending-up" tone="violet" />
        <x-stat label="Average loan size" :value="Money::format($credit['avg_loan'])" icon="coins" tone="ink" />
        <x-stat label="Default rate" :value="$pct($credit['default_rate'])" icon="percent" tone="ink" />
    </div>
    <p class="mt-3 text-xs text-ink-500">PAR30 = balance on loans with an installment 30+ days past due, as a share of total outstanding. Repayment rate = cumulative repaid ÷ cumulative disbursed (not annualised).</p>

    <div class="mt-6 card">
        <div class="card-header"><div><h3 class="card-title">Monthly disbursement trend</h3><p class="text-xs text-ink-500">Amount disbursed per month, last 12 months</p></div></div>
        <div class="card-body"><div class="h-64"><canvas id="trend" role="img" aria-label="Monthly disbursement amounts"></canvas></div></div>
        <div class="table-wrap border-t border-ink-100 dark:border-ink-800">
            <table class="table"><thead><tr><th>Month</th>@foreach ($trend['labels'] as $l)<th class="num">{{ $l }}</th>@endforeach</tr></thead>
                <tbody>
                <tr><td class="font-medium">Disbursed</td>@foreach ($trend['amounts'] as $v)<td class="num">{{ number_format($v) }}</td>@endforeach</tr>
                <tr><td class="font-medium">New loans</td>@foreach ($trend['counts'] as $v)<td class="num">{{ $v }}</td>@endforeach</tr>
                </tbody></table>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Loan status breakdown</h3></div>
            <table class="table">
                <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Outstanding</th></tr></thead>
                <tbody>@foreach ($status as $s)<tr><td><x-badge :status="$s['status']" /></td><td class="num">{{ $s['count'] }}</td><td class="num">{{ Money::format($s['outstanding']) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Arrears ageing</h3></div>
            <table class="table">
                <thead><tr><th>Days overdue</th><th class="num">Loans</th><th class="num">Outstanding</th><th>Share</th></tr></thead>
                <tbody>
                @php $ot = max($ageing->sum('outstanding'), 1); @endphp
                @foreach ($ageing as $bucket => $a)
                    <tr><td class="font-medium">{{ $bucket }}</td><td class="num">{{ $a['count'] }}</td><td class="num">{{ Money::format($a['outstanding']) }}</td>
                        <td style="min-width:120px"><div class="flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full {{ $bucket === 'Current' ? 'bg-emerald-500' : ($loop->index < 2 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $a['outstanding'] / $ot * 100 }}%"></div></div><span class="text-xs tabular-nums">{{ $pct($a['outstanding'] / $ot) }}</span></div></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Branch performance</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Branch</th><th class="num">Disbursed</th><th class="num">Outstanding</th><th class="num">Active loans</th><th class="num">Avg loan</th></tr></thead>
                    <tbody>@foreach ($branches as $b)<tr><td class="font-medium text-ink-900 dark:text-white">{{ $b['branch'] }}</td><td class="num">{{ Money::format($b['disbursed'], false) }}</td><td class="num">{{ Money::format($b['outstanding'], false) }}</td><td class="num">{{ $b['active'] }}</td><td class="num">{{ Money::format($b['avg'], false) }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">At-risk loans (30+ days)</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Loan</th><th>Borrower</th><th class="num">Days</th><th class="num">Arrears</th><th class="num">Outstanding</th></tr></thead>
                    <tbody>
                    @forelse ($atRisk as $l)
                        <tr><td><a href="{{ route('credit.loans.show', $l) }}" class="font-mono text-xs text-brand-700 dark:text-brand-300">{{ $l->loan_no }}</a></td><td>{{ $l->borrower->full_name }}</td><td class="num text-rose-600">{{ $l->daysOverdue() }}</td><td class="num">{{ Money::format($l->arrears(), false) }}</td><td class="num font-semibold">{{ Money::format($l->outstanding(), false) }}</td></tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="check-circle" title="No loans 30+ days overdue" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        makeChart(document.getElementById('trend'), {
            type: 'bar',
            data: { labels: @json($trend['labels']), datasets: [{ label: 'Disbursed', data: @json($trend['amounts']), backgroundColor: '#2f5cbb', hoverBackgroundColor: '#1c459f', borderRadius: 4, borderSkipped: 'bottom', maxBarThickness: 32 }] },
            options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + money(c.parsed.y) } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { maxTicksLimit: 5 } } } },
        });
    </script>
    @endpush
</x-layouts.app>
