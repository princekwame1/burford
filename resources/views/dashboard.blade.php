@php
    use App\Support\Money;
    $pct = fn ($v) => number_format($v * 100, 1).'%';
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp
<x-layouts.app title="Dashboard">
    <x-page-header :title="$greeting.', '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" subtitle="Here is how the business is performing today.">
        @if ($showSusu)
            <a href="{{ route('susu.collections.sheet') }}" class="btn btn-secondary"><x-icon name="clipboard" class="size-4" /> Open collection sheet</a>
        @endif
        @if (auth()->user()->hasRole('admin', 'manager'))
            <a href="{{ route('reports.index') }}" class="btn btn-dark"><x-icon name="chart" class="size-4" /> Reports</a>
        @endif
    </x-page-header>

    {{-- Hero strip --}}
    <div class="mb-6 overflow-hidden rounded-3xl bg-brand-950 p-6 text-white sm:p-8 relative">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_120%_at_100%_0%,rgba(217,150,1,.28),transparent_60%)]"></div>
        <div class="relative grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @if ($showSusu)
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gold-300">Collected today</p>
                    <p class="mt-2 font-display text-3xl font-extrabold tabular-nums">{{ Money::format($susu['today']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gold-300">Customer savings held</p>
                    <p class="mt-2 font-display text-3xl font-extrabold tabular-nums">{{ Money::format($susu['savings_balance']) }}</p>
                </div>
            @endif
            @if ($showCredit)
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gold-300">Loan book outstanding</p>
                    <p class="mt-2 font-display text-3xl font-extrabold tabular-nums">{{ Money::format($credit['outstanding']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gold-300">Repayments today</p>
                    <p class="mt-2 font-display text-3xl font-extrabold tabular-nums">{{ Money::format($credit['repaid_today']) }}</p>
                </div>
            @endif
        </div>
    </div>

    @isset($sms)
        {{-- ================= SMS CREDIT ================= --}}
        @php
            $w = $sms['wallet'];
            $hasBalance = $w && $w['ok'];
            $state = ! $sms['configured'] ? 'off' : (! $hasBalance ? 'unknown' : ($w['balance'] <= 0 ? 'empty' : ($w['balance'] < $sms['lowAt'] ? 'low' : 'ok')));
            $tone = [
                'ok' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300',
                'low' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                'empty' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                'unknown' => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                'off' => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
            ][$state];
            $balanceText = $hasBalance ? $w['currency'].' '.number_format($w['balance'], 2) : null;
        @endphp
        <div class="card mb-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center {{ in_array($state, ['low', 'empty']) ? ($state === 'empty' ? 'border-rose-200 dark:border-rose-500/30' : 'border-amber-200 dark:border-amber-500/30') : '' }}">
            <span class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $tone }}"><x-icon :name="in_array($state, ['low', 'empty']) ? 'alert' : 'message'" class="size-5" /></span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">SMS credit · TextTango</p>
                @if ($balanceText)
                    <p class="mt-0.5 font-display text-2xl font-extrabold tabular-nums text-ink-900 dark:text-white">{{ $balanceText }}
                        @if ($w['bonus'] > 0)<span class="text-sm font-semibold text-ink-500">+ {{ number_format($w['bonus'], 2) }} bonus</span>@endif</p>
                @else
                    <p class="mt-0.5 font-display text-lg font-bold text-ink-900 dark:text-white">{{ $state === 'off' ? 'Not connected' : 'Balance unavailable' }}</p>
                @endif
                <p class="mt-0.5 text-sm text-ink-500">
                    @switch($state)
                        @case('empty') Out of credit. Receipts and reminders are not being delivered until you top up. @break
                        @case('low') Running low. Top up soon so receipts and reminders keep going out. @break
                        @case('ok') {{ number_format($smsStats['sent']) }} sent and {{ number_format($smsStats['failed']) }} failed in the last 30 days. @break
                        @case('unknown') TextTango didn't answer just now: {{ $w['error'] ?? 'unknown error' }} @break
                        @default Messages are logged but not sent until an administrator adds the TextTango API token.
                    @endswitch
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($state === 'off')
                    @if (auth()->user()->isAdmin())<a href="{{ route('settings.edit') }}#sms" class="btn btn-dark"><x-icon name="settings" class="size-4" /> Connect TextTango</a>@endif
                @else
                    <form method="POST" action="{{ route('sms.wallet.refresh') }}">@csrf
                        <button class="btn btn-secondary" title="Check the balance again"><x-icon name="refresh" class="size-4" /> Refresh</button>
                    </form>
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'top-up')" class="btn {{ in_array($state, ['low', 'empty']) ? 'btn-primary' : 'btn-dark' }}"><x-icon name="wallet" class="size-4" /> Top up</button>
                @endif
            </div>
        </div>

        @if ($state !== 'off')
            <x-modal name="top-up" title="Top up SMS credit" :subtitle="$balanceText ? 'Current balance '.$balanceText : null">
                <div class="card-body space-y-5 text-sm">
                    <p class="text-ink-600 dark:text-ink-300">SMS credit is bought on TextTango's own website. They take the payment, so this app never sees your card or Mobile Money details.</p>
                    <ol class="space-y-3">
                        @foreach ([
                            ['Open your TextTango wallet', 'The button below opens it in a new tab. Sign in with the company\'s TextTango account if it asks.'],
                            ['Choose an amount and pay', 'Follow TextTango\'s steps to pay. Keep the receipt they give you for your records.'],
                            ['Come back and press Refresh balance', 'The new credit shows here once TextTango has it. If it hasn\'t arrived yet, wait a minute and try again.'],
                        ] as $i => [$step, $detail])
                            <li class="flex gap-3">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $i + 1 }}</span>
                                <div><p class="font-semibold text-ink-900 dark:text-white">{{ $step }}</p><p class="text-ink-500">{{ $detail }}</p></div>
                            </li>
                        @endforeach
                    </ol>
                    @if (count($sms['topUps']))
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Recent top-ups</p>
                            <ul class="mt-2 divide-y divide-ink-100 rounded-xl border border-ink-100 dark:divide-ink-800 dark:border-ink-800">
                                @foreach ($sms['topUps'] as $t)
                                    <li class="flex items-center justify-between gap-3 px-3 py-2">
                                        <span class="text-ink-500">{{ $t['date'] ? \Illuminate\Support\Carbon::parse($t['date'])->format('d M Y') : '—' }}@if($t['status']) · {{ ucfirst($t['status']) }}@endif</span>
                                        <span class="font-semibold tabular-nums text-ink-900 dark:text-white">{{ $t['currency'] }} {{ number_format($t['amount'], 2) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-ink-100 px-5 py-4 sm:flex-row sm:justify-end dark:border-ink-800">
                    <form method="POST" action="{{ route('sms.wallet.refresh') }}">@csrf
                        <button class="btn btn-secondary w-full"><x-icon name="refresh" class="size-4" /> Refresh balance</button>
                    </form>
                    <a href="{{ $sms['topUpUrl'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><x-icon name="wallet" class="size-4" /> Open TextTango wallet</a>
                </div>
            </x-modal>
        @endif
    @endisset

    @if ($showSusu)
        {{-- ================= SUSU ================= --}}
        <div class="mb-3 flex items-center gap-2">
            <span class="grid size-7 place-items-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"><x-icon name="coins" class="size-4" /></span>
            <h2 class="font-display text-lg font-bold text-ink-900 dark:text-white">Susu collections</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 2xl:grid-cols-6">
            <x-stat label="Total expected" :value="Money::format($susu['expected'])" icon="calendar" tone="blue" />
            <x-stat label="Total collected" :value="Money::format($susu['collected'])" icon="coins" tone="brand" />
            <x-stat label="Outstanding" :value="Money::format($susu['outstanding'])" icon="clock" tone="amber" />
            <x-stat label="Collection rate" :value="$pct($susu['rate'])" icon="percent" tone="violet">
                <div class="h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ min($susu['rate'] * 100, 100) }}%"></div></div>
            </x-stat>
            <x-stat label="Customers" :value="number_format($susu['customers'])" icon="users" tone="ink" :sub="$susu['active_customers'].' active'" :href="route('susu.customers.index')" />
            <x-stat label="Active collectors" :value="$susu['active_collectors']" icon="id-card" tone="ink" />
        </div>

        {{-- Management control panel --}}
        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Unreconciled variance', Money::format($susu['unreconciled_variance']), $susu['open_reconciliations'].' open reconciliation(s)', 'scale', abs($susu['unreconciled_variance']) > 0.009 ? 'rose' : 'brand', auth()->user()->hasRole('admin','manager') ? route('susu.reconciliations.index', ['status' => 'Open']) : null],
                ['Customer withdrawals', Money::format($susu['withdrawals']), 'Commission earned '.Money::format($susu['commission']), 'arrow-up-circle', 'blue', route('susu.withdrawals.index')],
                ['Paid transactions', number_format($susu['paid_count']), 'Receipts fully paid', 'check-circle', 'brand', route('susu.collections.index', ['status' => 'Paid'])],
                ['Partial / unpaid', number_format($susu['partial_count']), 'Receipts with a shortfall', 'alert', 'amber', route('susu.collections.index', ['status' => 'Partial'])],
            ] as [$l, $v, $s, $i, $t, $h])
                <x-stat :label="$l" :value="$v" :sub="$s" :icon="$i" :tone="$t" :href="$h" />
            @endforeach
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-5">
            <div class="card xl:col-span-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Daily expected vs collected</h3>
                        <p class="text-xs text-ink-500">Last 60 days</p>
                    </div>
                </div>
                <div class="card-body"><div class="h-72"><canvas id="trendChart" aria-label="Daily expected versus collected, last 60 days" role="img"></canvas></div></div>
            </div>
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <h3 class="card-title">Customer portfolio</h3>
                    <a href="{{ route('susu.customers.index') }}" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">View all</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Customer</th><th class="num">Collected</th><th class="num">Owing</th><th class="num">Rate</th></tr></thead>
                        <tbody>
                        @forelse ($portfolio as $c)
                            <tr>
                                <td><a href="{{ route('susu.customers.show', $c) }}" class="font-medium text-ink-900 hover:text-brand-700 dark:text-white">{{ $c->name }}</a><div class="text-xs text-ink-400">{{ $c->code }}</div></td>
                                <td class="num">{{ Money::format($c->collected, false) }}</td>
                                <td class="num {{ $c->outstanding_total > 0 ? 'text-amber-600' : '' }}">{{ Money::format($c->outstanding_total, false) }}</td>
                                <td class="num"><x-badge :tone="$c->rate >= 1 ? 'green' : ($c->rate >= .9 ? 'amber' : 'rose')">{{ $pct($c->rate) }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="users" title="No customers yet" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($showCredit)
        {{-- ================= MICRO CREDIT ================= --}}
        <div class="mb-3 mt-10 flex items-center gap-2">
            <span class="grid size-7 place-items-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"><x-icon name="landmark" class="size-4" /></span>
            <h2 class="font-display text-lg font-bold text-ink-900 dark:text-white">Micro credit portfolio</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Total disbursed" :value="Money::format($credit['disbursed'])" icon="banknote" tone="brand" :sub="$credit['total_loans'].' loan(s) in total'" :href="route('credit.loans.index')" />
            <x-stat label="Total outstanding" :value="Money::format($credit['outstanding'])" icon="wallet" tone="blue" :sub="'Interest booked '.Money::format($credit['interest_expected'])" />
            <x-stat label="Active borrowers" :value="$credit['active_borrowers']" icon="users" tone="ink" :href="route('credit.borrowers.index')" />
            <x-stat label="Arrears" :value="Money::format($credit['arrears'])" icon="alert" :tone="$credit['arrears'] > 0 ? 'rose' : 'brand'" :sub="$credit['overdue_count'].' loan(s) behind schedule'" :href="route('credit.loans.index', ['status' => 'Overdue'])" />
            <x-stat label="Portfolio at risk (PAR30)" :value="$pct($credit['par30'])" icon="shield" :tone="$credit['par30'] > .05 ? 'rose' : 'brand'" :sub="Money::format($credit['at_risk']).' 30+ days late'" />
            <x-stat label="Repayment rate" :value="$pct($credit['repayment_rate'])" icon="trending-up" tone="violet" sub="Repaid ÷ disbursed" />
            <x-stat label="Average loan size" :value="Money::format($credit['avg_loan'])" icon="coins" tone="ink" />
            <x-stat label="Default rate" :value="$pct($credit['default_rate'])" icon="percent" :tone="$credit['default_rate'] > 0 ? 'rose' : 'ink'" />
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div><h3 class="card-title">Monthly disbursements</h3><p class="text-xs text-ink-500">Amount disbursed per month, last 6 months</p></div>
                </div>
                <div class="card-body"><div class="h-64"><canvas id="disbChart" aria-label="Monthly disbursement amounts" role="img"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Loan status breakdown</h3></div>
                <div class="card-body space-y-4">
                    @php $totalLoans = max($statusBreakdown->sum('count'), 1); $statusColor = ['Active' => 'bg-emerald-500', 'Overdue' => 'bg-amber-500', 'Defaulted' => 'bg-rose-500', 'Closed' => 'bg-ink-400']; @endphp
                    @foreach ($statusBreakdown as $s)
                        <a href="{{ route('credit.loans.index', ['status' => $s['status']]) }}" class="block group">
                            <div class="flex items-baseline justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-ink-800 dark:text-ink-100"><span class="size-2 rounded-full {{ $statusColor[$s['status']] }}"></span>{{ $s['status'] }}</span>
                                <span class="tabular-nums text-ink-500"><span class="font-semibold text-ink-900 dark:text-white">{{ $s['count'] }}</span> · {{ Money::format($s['outstanding']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                                <div class="h-full rounded-full {{ $statusColor[$s['status']] }} transition-all group-hover:opacity-80" style="width: {{ $s['count'] / $totalLoans * 100 }}%"></div>
                            </div>
                        </a>
                    @endforeach
                    <div class="grid grid-cols-2 gap-3 border-t border-ink-100 pt-4 dark:border-ink-800">
                        <a href="{{ route('credit.applications.index', ['status' => 'Pending']) }}" class="rounded-xl bg-amber-50 p-3 dark:bg-amber-500/10">
                            <p class="text-xs text-amber-700 dark:text-amber-300">Pending applications</p>
                            <p class="mt-1 font-display text-xl font-bold text-amber-900 dark:text-amber-100">{{ $credit['pending_applications'] }}</p>
                        </a>
                        <a href="{{ route('credit.applications.index', ['status' => 'Approved']) }}" class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Awaiting disbursement</p>
                            <p class="mt-1 font-display text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ $credit['approved_applications'] }}</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Loans in arrears</h3>
                    <a href="{{ route('credit.loans.index', ['status' => 'Overdue']) }}" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">View all</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Loan</th><th class="num">Arrears</th><th class="num">Days late</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($overdueLoans as $loan)
                            <tr>
                                <td><a href="{{ route('credit.loans.show', $loan) }}" class="font-medium text-ink-900 hover:text-brand-700 dark:text-white">{{ $loan->borrower->full_name }}</a><div class="text-xs text-ink-400">{{ $loan->loan_no }} · {{ $loan->borrower->phone }}</div></td>
                                <td class="num font-semibold text-rose-600">{{ Money::format($loan->arrears()) }}</td>
                                <td class="num"><x-badge :tone="$loan->daysOverdue() >= 30 ? 'rose' : 'amber'">{{ $loan->daysOverdue() }} days</x-badge></td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('credit.loans.remind', $loan) }}"
                                        data-confirm-title="Send SMS reminder?" data-confirm="{{ $loan->borrower->full_name }} ({{ $loan->borrower->phone }}) is {{ $loan->daysOverdue() }} days late with {{ Money::format($loan->arrears()) }} in arrears." data-confirm-button="Send reminder">@csrf
                                        <button class="btn btn-ghost btn-sm" title="Send SMS reminder"><x-icon name="send" class="size-3.5" /> Remind</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="check-circle" title="No loans in arrears" text="Every borrower is up to date." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Applications awaiting decision</h3>
                    <a href="{{ route('credit.applications.create') }}" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-3.5" /> New</a>
                </div>
                <div class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($pendingApplications as $app)
                        <a href="{{ route('credit.applications.show', $app) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-ink-50/60 dark:hover:bg-ink-800/40">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-ink-100 text-sm font-bold text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ mb_substr($app->borrower->full_name, 0, 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink-900 dark:text-white">{{ $app->borrower->full_name }}</p>
                                <p class="text-xs text-ink-500">{{ $app->application_no }} · {{ $app->purpose ?? 'General' }} · {{ $app->application_date->diffForHumans() }}</p>
                            </div>
                            <span class="font-display font-bold tabular-nums text-ink-900 dark:text-white">{{ Money::format($app->amount_requested) }}</span>
                            <x-icon name="chevron-right" class="size-4 text-ink-400" />
                        </a>
                    @empty
                        <x-empty icon="file-text" title="No pending applications" />
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($showSusu && $recentCollections->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Latest receipts</h3>
                <a href="{{ route('susu.collections.index') }}" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">All receipts</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Receipt</th><th>Date</th><th>Customer</th><th>Collector</th><th>Method</th><th class="num">Collected</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($recentCollections as $c)
                        <tr>
                            <td class="font-mono text-xs">{{ $c->receipt_no }}</td>
                            <td>{{ $c->date->format('d M Y') }}</td>
                            <td class="font-medium text-ink-900 dark:text-white">{{ $c->customer->name }}</td>
                            <td>{{ $c->collector?->name }}</td>
                            <td>{{ $c->payment_method }}</td>
                            <td class="num font-semibold">{{ Money::format($c->collected) }}</td>
                            <td><x-badge :status="$c->status" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @push('scripts')
    <script type="module">
        @if ($showSusu)
        makeChart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: @json($trend['labels']),
                datasets: [
                    { label: 'Expected', data: @json($trend['expected']), borderColor: '#d99601', backgroundColor: '#d99601', borderWidth: 2, borderDash: [5, 4], pointRadius: 0, pointHoverRadius: 5, tension: .3 },
                    { label: 'Collected', data: @json($trend['collected']), borderColor: '#2f5cbb', backgroundColor: 'rgba(47,92,187,.12)', fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, tension: .3 },
                ],
            },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + money(c.parsed.y) } } },
                scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }, y: { beginAtZero: true, ticks: { maxTicksLimit: 5 } } },
            },
        });
        @endif
        @if ($showCredit)
        makeChart(document.getElementById('disbChart'), {
            type: 'bar',
            data: {
                labels: @json($disbursements['labels']),
                datasets: [{ label: 'Disbursed', data: @json($disbursements['amounts']), backgroundColor: '#2f5cbb', hoverBackgroundColor: '#1c459f', borderRadius: 4, borderSkipped: 'bottom', maxBarThickness: 36 }],
            },
            options: {
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + money(c.parsed.y) } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { maxTicksLimit: 5 } } },
            },
        });
        @endif
    </script>
    @endpush
</x-layouts.app>
