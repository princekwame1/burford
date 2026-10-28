@php use App\Support\Money; @endphp
<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" :subtitle="$customer->code.' · '.$customer->plan.' plan · '.($customer->collector?->name ?? 'No collector')" :back="route('susu.customers.index')">
        <a href="{{ route('susu.customers.edit', $customer) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
        <a href="{{ route('susu.withdrawals.create', ['customer' => $customer->id]) }}" class="btn btn-secondary"><x-icon name="arrow-up-circle" class="size-4" /> Withdrawal</a>
        <a href="{{ route('susu.collections.create', ['customer' => $customer->id]) }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Record collection</a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Current balance" :value="Money::format($balance)" icon="wallet" tone="brand" />
        <x-stat label="Total collected" :value="Money::format($stats['collected'])" icon="coins" tone="blue" :sub="$stats['payments'].' payment(s)'" />
        <x-stat label="Total withdrawn" :value="Money::format($stats['withdrawn'])" icon="arrow-up-circle" tone="amber" />
        <x-stat label="Collection rate" :value="$stats['expected'] > 0 ? number_format($stats['collected'] / $stats['expected'] * 100, 1).'%' : '—'" icon="percent" tone="violet" :sub="'Short by '.Money::format($stats['expected'] - $stats['collected'])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profile</h3><x-badge :status="$customer->status" /></div>
            <dl class="card-body space-y-3 text-sm">
                @foreach ([
                    ['phone', 'Phone', $customer->phone], ['map-pin', 'Address', $customer->address], ['id-card', 'Ghana Card', $customer->ghana_card],
                    ['user', 'Collector', $customer->collector?->name], ['coins', 'Contribution', Money::format($customer->contribution).' / '.strtolower($customer->frequency)],
                    ['calendar', 'Start date', $customer->start_date?->format('d M Y')], ['wallet', 'Opening balance', Money::format($customer->opening_balance)],
                ] as [$i, $l, $v])
                    <div class="flex items-start gap-3">
                        <x-icon :name="$i" class="mt-0.5 size-4 text-ink-400" />
                        <dt class="w-28 shrink-0 text-ink-500">{{ $l }}</dt>
                        <dd class="font-medium text-ink-900 dark:text-white">{{ $v ?: '—' }}</dd>
                    </div>
                @endforeach
                @if ($customer->notes)<p class="rounded-xl bg-ink-50 p-3 text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ $customer->notes }}</p>@endif
            </dl>
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header"><h3 class="card-title">Customer ledger</h3><span class="text-xs text-ink-500">{{ $ledger->count() }} entries</span></div>
            <div class="table-wrap max-h-[560px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Date</th><th>Transaction</th><th>Reference</th><th class="num">Credit</th><th class="num">Debit</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                    @foreach ($ledger as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td>
                                <span class="font-medium text-ink-900 dark:text-white">{{ $row['type'] }}</span>
                                @if ($row['notes'])<div class="text-xs text-ink-400">{{ $row['notes'] }}</div>@endif
                            </td>
                            <td class="font-mono text-xs">{{ $row['reference'] }}</td>
                            <td class="num text-emerald-600">{{ $row['credit'] ? Money::format($row['credit'], false) : '' }}</td>
                            <td class="num text-rose-600">{{ $row['debit'] ? Money::format($row['debit'], false) : '' }}</td>
                            <td class="num font-semibold text-ink-900 dark:text-white">{{ Money::format($row['balance'], false) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
