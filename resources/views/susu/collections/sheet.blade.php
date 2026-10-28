@php use App\Support\Money; @endphp
<x-layouts.app title="Collection sheet">
    <x-page-header title="Collection sheet" subtitle="Record a whole round in one go — enter what each customer paid and save.">
        <a href="{{ route('susu.collections.index') }}" class="btn btn-secondary"><x-icon name="receipt" class="size-4" /> All receipts</a>
    </x-page-header>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        @unless ($lockCollector)
            <div class="sm:w-60">
                <label class="label">Collector</label>
                <select name="collector" class="input" onchange="this.form.submit()">
                    @foreach ($collectors as $c)<option value="{{ $c->id }}" @selected($collectorId == $c->id)>{{ $c->name }} — {{ $c->territory }}</option>@endforeach
                </select>
            </div>
        @endunless
        <div class="sm:w-48">
            <label class="label">Date</label>
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="input" onchange="this.form.submit()">
        </div>
        <noscript><button class="btn btn-dark">Load</button></noscript>
    </form>

    <form method="POST" action="{{ route('susu.collections.sheet.store') }}"
        x-data="{ rows: {}, get total() { return Object.values(this.rows).reduce((s, r) => s + (parseFloat(r) || 0), 0) }, get count() { return Object.values(this.rows).filter(r => r !== '' && r !== null).length } }"
        data-confirm-title="Save these collections?" data-confirm-button="Save collections"
        :data-confirm="`${count} ${count === 1 ? 'entry' : 'entries'} totalling ${money(total)}.`">
        @csrf
        <input type="hidden" name="collector_id" value="{{ $collectorId }}">
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Plan</th><th class="num">Expected</th><th style="width:170px">Collected</th><th style="width:170px">Method</th><th>Today</th></tr></thead>
                    <tbody>
                    @forelse ($customers as $c)
                        @php $done = $existing->get($c->id); @endphp
                        <tr class="{{ $done ? 'bg-emerald-50/40 dark:bg-emerald-500/5' : '' }}">
                            <td><span class="font-medium text-ink-900 dark:text-white">{{ $c->name }}</span><div class="text-xs text-ink-400">{{ $c->code }} · {{ $c->phone }}</div></td>
                            <td>{{ $c->plan }}</td>
                            <td class="num">
                                <input type="hidden" name="rows[{{ $c->id }}][expected]" value="{{ $c->contribution }}">
                                {{ Money::format($c->contribution, false) }}
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="0.01" min="0" name="rows[{{ $c->id }}][collected]" x-model="rows[{{ $c->id }}]" placeholder="0.00" class="input input-sm">
                                    <button type="button" @click="rows[{{ $c->id }}] = '{{ $c->contribution }}'" class="btn btn-ghost btn-sm px-2" title="Paid in full"><x-icon name="check" class="size-3.5" /></button>
                                </div>
                            </td>
                            <td>
                                <select name="rows[{{ $c->id }}][method]" class="input input-sm">
                                    <option>Cash</option><option>Mobile Money</option><option>Bank</option>
                                </select>
                            </td>
                            <td>
                                @if ($done)
                                    <x-badge :status="$done->status">{{ Money::format($done->collected, false) }} · {{ $done->status }}</x-badge>
                                @else
                                    <span class="text-xs text-ink-400">Not yet</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="users" title="No active customers for this collector" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($customers->isNotEmpty())
                <div class="flex flex-col gap-3 border-t border-ink-100 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-ink-800">
                    <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300"><input type="checkbox" name="send_sms" value="1" checked class="checkbox"> SMS each customer their receipt</label>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-ink-500"><span class="font-semibold text-ink-900 dark:text-white" x-text="count"></span> entries · <span class="font-display text-lg font-bold text-ink-900 dark:text-white" x-text="money(total)"></span></p>
                        <button class="btn btn-primary" :disabled="count === 0"><x-icon name="check" class="size-4" /> Save collections</button>
                    </div>
                </div>
            @endif
        </div>
    </form>
</x-layouts.app>
