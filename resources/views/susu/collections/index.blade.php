@php use App\Support\Money; $isCollector = auth()->user()->role === 'collector'; @endphp
<x-layouts.app title="Collection receipts">
    <x-page-header title="Daily collections" subtitle="Every susu receipt, with expected vs collected and payment status.">
        @unless ($isCollector)
            <a href="{{ route('reports.export', 'collections') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Export</a>
        @endunless
        <a href="{{ route('susu.collections.sheet') }}" class="btn btn-secondary"><x-icon name="clipboard" class="size-4" /> Collection sheet</a>
        <a href="{{ route('susu.collections.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New receipt</a>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Expected (filtered)" :value="Money::format($totals->e)" icon="calendar" tone="blue" />
        <x-stat label="Collected (filtered)" :value="Money::format($totals->c)" icon="coins" tone="brand" />
        <x-stat label="Outstanding (filtered)" :value="Money::format($totals->o)" icon="clock" tone="amber" />
    </div>

    <div class="card">
        <x-filters :action="route('susu.collections.index')" placeholder="Receipt no., customer…">
            @unless ($isCollector)
                <select name="collector" class="input input-sm sm:w-40">
                    <option value="">All collectors</option>
                    @foreach ($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            @endunless
            <select name="status" class="input input-sm sm:w-32">
                <option value="">Any status</option>
                @foreach (['Paid', 'Partial', 'Unpaid'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
            <select name="method" class="input input-sm sm:w-36">
                <option value="">Any method</option>
                @foreach (['Cash', 'Mobile Money', 'Bank'] as $m)<option @selected(request('method') === $m)>{{ $m }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input input-sm sm:w-40" title="From">
            <input type="date" name="to" value="{{ request('to') }}" class="input input-sm sm:w-40" title="To">
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Receipt</th><th>Date</th><th>Customer</th><th>Collector</th><th class="num">Expected</th><th class="num">Collected</th><th class="num">Outstanding</th><th>Method</th><th>Status</th><th>Deposit</th><th></th></tr></thead>
                <tbody>
                @forelse ($collections as $c)
                    <tr>
                        <td class="font-mono text-xs">{{ $c->receipt_no }}</td>
                        <td>{{ $c->date->format('d M Y') }}</td>
                        <td><a href="{{ route('susu.customers.show', $c->susu_customer_id) }}" class="font-medium text-ink-900 hover:text-brand-700 dark:text-white">{{ $c->customer?->name }}</a><div class="text-xs text-ink-400">{{ $c->customer?->code }}</div></td>
                        <td>{{ $c->collector?->name }}</td>
                        <td class="num">{{ Money::format($c->expected, false) }}</td>
                        <td class="num font-semibold text-ink-900 dark:text-white">{{ Money::format($c->collected, false) }}</td>
                        <td class="num {{ $c->outstanding > 0 ? 'text-amber-600' : 'text-ink-400' }}">{{ Money::format($c->outstanding, false) }}</td>
                        <td>{{ $c->payment_method }}</td>
                        <td><x-badge :status="$c->status" /></td>
                        <td class="text-xs">{{ $c->deposit_ref }}</td>
                        <td class="text-right">
                            <a href="{{ route('susu.collections.receipt', $c) }}" class="btn btn-ghost btn-sm" title="Print receipt"><x-icon name="printer" class="size-3.5" /></a>
                            <a href="{{ route('susu.collections.edit', $c) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-3.5" /></a>
                            @unless ($isCollector)<x-delete-button :action="route('susu.collections.destroy', $c)" title="Delete this receipt?" confirm="The collection will be removed from the customer's balance." />@endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><x-empty icon="receipt" title="No receipts match" text="Try a different filter or record a new collection." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($collections->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $collections->links() }}</div>@endif
    </div>
</x-layouts.app>
