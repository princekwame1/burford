@php use App\Support\Money; @endphp
<x-layouts.app title="Reconciliation">
    <x-page-header title="Daily collection reconciliation" subtitle="Cash and Mobile Money counted against what the system recorded, per collector per day.">
        <a href="{{ route('susu.reconciliations.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New reconciliation</a>
    </x-page-header>
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="System collections" :value="Money::format($totals->s)" icon="receipt" tone="blue" />
        <x-stat label="Total counted" :value="Money::format($totals->t)" icon="banknote" tone="brand" />
        <x-stat label="Variance" :value="Money::format($totals->v)" icon="scale" :tone="abs((float) $totals->v) > 0.009 ? 'rose' : 'brand'" sub="Counted − system" />
        <x-stat label="Deposited" :value="Money::format($totals->d)" icon="landmark" tone="ink" />
    </div>
    <div class="card">
        <x-filters :action="route('susu.reconciliations.index')" placeholder="Search…">
            <select name="collector" class="input input-sm sm:w-40">
                <option value="">All collectors</option>
                @foreach ($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <select name="status" class="input input-sm sm:w-36">
                <option value="">Any status</option>
                @foreach (['Open', 'Reconciled', 'Escalated'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input input-sm sm:w-40">
            <input type="date" name="to" value="{{ request('to') }}" class="input input-sm sm:w-40">
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Collector</th><th class="num">System</th><th class="num">Cash</th><th class="num">MoMo</th><th class="num">Total counted</th><th class="num">Variance</th><th class="num">Deposit</th><th>Ref.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($reconciliations as $r)
                    <tr>
                        <td>{{ $r->date->format('d M Y') }}</td>
                        <td class="font-medium text-ink-900 dark:text-white">{{ $r->collector?->name }}</td>
                        <td class="num">{{ Money::format($r->system_collections, false) }}</td>
                        <td class="num">{{ Money::format($r->cash_counted, false) }}</td>
                        <td class="num">{{ Money::format($r->mobile_money, false) }}</td>
                        <td class="num font-semibold">{{ Money::format($r->total_counted, false) }}</td>
                        <td class="num font-semibold {{ $r->variance < -0.009 ? 'text-rose-600' : ($r->variance > 0.009 ? 'text-amber-600' : 'text-emerald-600') }}">{{ ($r->variance > 0 ? '+' : '').Money::format($r->variance, false) }}</td>
                        <td class="num">{{ Money::format($r->deposit_amount, false) }}</td>
                        <td class="text-xs">{{ $r->deposit_ref }}</td>
                        <td><x-badge :status="$r->status" /></td>
                        <td class="text-right">
                            <a href="{{ route('susu.reconciliations.edit', $r) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-3.5" /></a>
                            <x-delete-button :action="route('susu.reconciliations.destroy', $r)" title="Delete this reconciliation?" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><x-empty icon="scale" title="No reconciliations yet" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($reconciliations->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $reconciliations->links() }}</div>@endif
    </div>
</x-layouts.app>
