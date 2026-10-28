@php use App\Support\Money; $pct = fn ($v) => number_format($v * 100, 1).'%'; @endphp
<x-layouts.app title="Susu report">
    <x-page-header title="Susu collections report" :subtitle="$from->format('d M Y').' – '.$to->format('d M Y')" :back="route('reports.index')">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="input input-sm w-40">
            <span class="text-ink-400">→</span>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="input input-sm w-40">
            <button class="btn btn-dark btn-sm">Apply</button>
        </form>
        <button onclick="window.print()" class="btn btn-secondary btn-sm"><x-icon name="printer" class="size-3.5" /> Print</button>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Expected" :value="Money::format($summary['expected'])" icon="calendar" tone="blue" />
        <x-stat label="Collected" :value="Money::format($summary['collected'])" icon="coins" tone="brand" />
        <x-stat label="Outstanding" :value="Money::format($summary['outstanding'])" icon="clock" tone="amber" />
        <x-stat label="Collection rate" :value="$pct($summary['rate'])" icon="percent" tone="violet" />
        <x-stat label="Withdrawals" :value="Money::format($summary['withdrawals'])" icon="arrow-up-circle" tone="ink" />
        <x-stat label="Commission" :value="Money::format($summary['commission'])" icon="banknote" tone="ink" />
        <x-stat label="Paid receipts" :value="$summary['paid_count']" icon="check-circle" tone="brand" />
        <x-stat label="Partial / unpaid" :value="$summary['partial_count']" icon="alert" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h3 class="card-title">Collector performance</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Collector</th><th class="num">Customers</th><th class="num">Expected</th><th class="num">Collected</th><th class="num">Rate</th><th class="num">Target (period)</th><th>vs target</th><th class="num">Open variance</th></tr></thead>
                    <tbody>
                    @foreach ($collectors as $row)
                        @php $vs = $row['target'] > 0 ? $row['collected'] / $row['target'] : 0; @endphp
                        <tr>
                            <td class="font-medium text-ink-900 dark:text-white">{{ $row['collector']->name }}<div class="text-xs text-ink-400">{{ $row['collector']->territory }}</div></td>
                            <td class="num">{{ $row['customers'] }}</td>
                            <td class="num">{{ Money::format($row['expected'], false) }}</td>
                            <td class="num font-semibold">{{ Money::format($row['collected'], false) }}</td>
                            <td class="num">{{ $pct($row['rate']) }}</td>
                            <td class="num">{{ Money::format($row['target'], false) }}</td>
                            <td style="min-width:120px"><div class="flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ min($vs * 100, 100) }}%"></div></div><span class="text-xs tabular-nums">{{ $pct($vs) }}</span></div></td>
                            <td class="num {{ abs($row['variance']) > 0.009 ? 'text-rose-600 font-semibold' : '' }}">{{ Money::format($row['variance'], false) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment method mix</h3></div>
            <div class="card-body space-y-4">
                @php $mt = max($methods->sum(), 1); @endphp
                @forelse ($methods as $m => $v)
                    <div>
                        <div class="flex justify-between text-sm"><span class="font-medium">{{ $m }}</span><span class="tabular-nums text-ink-500">{{ Money::format($v) }} · {{ $pct($v / $mt) }}</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full {{ $m === 'Cash' ? 'bg-brand-500' : 'bg-gold-500' }}" style="width: {{ $v / $mt * 100 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-ink-400">No collections in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daily summary</h3></div>
            <div class="table-wrap max-h-[480px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Date</th><th class="num">Receipts</th><th class="num">Expected</th><th class="num">Collected</th><th class="num">Outstanding</th></tr></thead>
                    <tbody>
                    @forelse ($daily as $d)
                        <tr><td>{{ \Carbon\Carbon::parse($d->date)->format('D d M') }}</td><td class="num">{{ $d->receipts }}</td><td class="num">{{ Money::format($d->expected, false) }}</td><td class="num font-semibold">{{ Money::format($d->collected, false) }}</td><td class="num {{ $d->outstanding > 0 ? 'text-amber-600' : '' }}">{{ Money::format($d->outstanding, false) }}</td></tr>
                    @empty
                        <tr><td colspan="5"><x-empty title="No collections in this period" /></td></tr>
                    @endforelse
                    </tbody>
                    @if ($daily->isNotEmpty())
                        <tfoot><tr><td>Total</td><td class="num">{{ $daily->sum('receipts') }}</td><td class="num">{{ Money::format($daily->sum('expected'), false) }}</td><td class="num">{{ Money::format($daily->sum('collected'), false) }}</td><td class="num">{{ Money::format($daily->sum('outstanding'), false) }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Customer portfolio (all time)</h3></div>
            <div class="table-wrap max-h-[480px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Customer</th><th class="num">Expected</th><th class="num">Collected</th><th class="num">Outstanding</th><th class="num">Rate</th></tr></thead>
                    <tbody>
                    @foreach ($portfolio as $c)
                        <tr><td class="font-medium text-ink-900 dark:text-white">{{ $c->name }}</td><td class="num">{{ Money::format($c->expected, false) }}</td><td class="num">{{ Money::format($c->collected, false) }}</td><td class="num">{{ Money::format($c->outstanding_total, false) }}</td><td class="num">{{ $pct($c->rate) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
