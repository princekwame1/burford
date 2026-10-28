@php use App\Support\Money; $isCollector = auth()->user()->role === 'collector'; @endphp
<x-layouts.app title="Withdrawals">
    <x-page-header title="Customer withdrawals" subtitle="Payouts from susu savings, checked against each customer's balance.">
        @unless ($isCollector)<a href="{{ route('reports.export', 'withdrawals') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Export</a>@endunless
        <a href="{{ route('susu.withdrawals.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New withdrawal</a>
    </x-page-header>
    <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <x-stat label="Total withdrawn (filtered)" :value="Money::format($total)" icon="arrow-up-circle" tone="amber" />
        <x-stat label="Commission earned (filtered)" :value="Money::format($commission)" icon="coins" tone="brand" />
    </div>
    <div class="card">
        <x-filters :action="route('susu.withdrawals.index')" placeholder="Withdrawal no., customer…">
            <select name="type" class="input input-sm sm:w-36">
                <option value="">Any type</option>
                @foreach (['Normal', 'Emergency', 'Closure'] as $t)<option @selected(request('type') === $t)>{{ $t }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input input-sm sm:w-40">
            <input type="date" name="to" value="{{ request('to') }}" class="input input-sm sm:w-40">
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Withdrawal</th><th>Date</th><th>Customer</th><th class="num">Amount</th><th class="num">Commission</th><th>Type</th><th>Approved by</th><th>Method</th><th class="num">Balance after</th><th></th></tr></thead>
                <tbody>
                @forelse ($withdrawals as $w)
                    <tr>
                        <td class="font-mono text-xs">{{ $w->withdrawal_no }}</td>
                        <td>{{ $w->date->format('d M Y') }}</td>
                        <td><a href="{{ route('susu.customers.show', $w->susu_customer_id) }}" class="font-medium text-ink-900 hover:text-brand-700 dark:text-white">{{ $w->customer?->name }}</a><div class="text-xs text-ink-400">{{ $w->customer?->code }}</div></td>
                        <td class="num font-semibold text-ink-900 dark:text-white">{{ Money::format($w->amount, false) }}</td>
                        <td class="num">{{ Money::format($w->commission, false) }}</td>
                        <td><x-badge :tone="$w->type === 'Normal' ? 'ink' : ($w->type === 'Emergency' ? 'amber' : 'blue')">{{ $w->type }}</x-badge></td>
                        <td>{{ $w->approved_by }}</td>
                        <td>{{ $w->payment_method }}</td>
                        <td class="num">{{ Money::format($w->balance_after, false) }}</td>
                        <td class="text-right">@unless ($isCollector)<x-delete-button :action="route('susu.withdrawals.destroy', $w)" title="Reverse this withdrawal?" confirm="The amount will be returned to the customer's balance." button="Yes, reverse" />@endunless</td>
                    </tr>
                @empty
                    <tr><td colspan="10"><x-empty icon="arrow-up-circle" title="No withdrawals recorded" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($withdrawals->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $withdrawals->links() }}</div>@endif
    </div>
</x-layouts.app>
