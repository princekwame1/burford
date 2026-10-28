@php $editing = $reconciliation->exists; $sys = $system->map(fn ($s) => ['total' => (float) $s->total, 'momo' => (float) $s->momo]); @endphp
<x-layouts.app :title="$editing ? 'Edit reconciliation' : 'New reconciliation'">
    <x-page-header :title="$editing ? 'Edit reconciliation' : 'Reconcile a collector\'s day'" subtitle="System collections are taken from the receipts recorded for that collector and date." :back="route('susu.reconciliations.index')" />
    <form method="POST" action="{{ $editing ? route('susu.reconciliations.update', $reconciliation) : route('susu.reconciliations.store') }}"
        x-data="{ sys: @js($sys), collector: @js((string) old('collector_id', $reconciliation->collector_id)), cash: @js((float) old('cash_counted', $reconciliation->cash_counted ?? 0)), momo: @js((float) old('mobile_money', $reconciliation->mobile_money ?? 0)),
            get system() { return this.sys[this.collector]?.total ?? 0 }, get counted() { return (this.cash || 0) + (this.momo || 0) }, get variance() { return this.counted - this.system } }"
        class="grid gap-6 lg:grid-cols-3">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card lg:col-span-2">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', $reconciliation->date?->toDateString()) }}" class="input" required
                        @unless($editing) onchange="window.location='{{ route('susu.reconciliations.create') }}?date='+this.value" @endunless>
                    @unless($editing)<p class="help">Changing the date reloads the system totals.</p>@endunless
                </div>
                <div>
                    <label class="label">Collector <span class="text-rose-500">*</span></label>
                    <select name="collector_id" x-model="collector" class="input" required>
                        <option value="">Select…</option>
                        @foreach ($collectors as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    @error('collector_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Cash counted <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="cash_counted" x-model.number="cash" class="input" required>
                </div>
                <div>
                    <label class="label">Mobile Money <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="mobile_money" x-model.number="momo" class="input" required>
                    <p class="help" x-show="sys[collector]">System MoMo receipts: <span x-text="money(sys[collector]?.momo)"></span></p>
                </div>
                <x-field label="Deposit amount" name="deposit_amount" type="number" :value="$reconciliation->deposit_amount" prefix="GH₵" help="Defaults to total counted." />
                <x-field label="Deposit ref." name="deposit_ref" :value="$reconciliation->deposit_ref" />
                <x-select label="Status" name="status" :value="$reconciliation->status" :options="['Open', 'Reconciled', 'Escalated']" />
                <x-field label="Notes" name="notes" type="textarea" :value="$reconciliation->notes" class="sm:col-span-2" />
            </div>
        </div>
        <div class="space-y-4">
            <div class="card card-body">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Summary</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">System collections</dt><dd class="font-semibold tabular-nums" x-text="money(system)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Total counted</dt><dd class="font-semibold tabular-nums" x-text="money(counted)"></dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-3 dark:border-ink-800"><dt class="text-ink-500">Variance</dt>
                        <dd class="font-bold tabular-nums" :class="Math.abs(variance) < 0.01 ? 'text-emerald-600' : (variance < 0 ? 'text-rose-600' : 'text-amber-600')" x-text="(variance > 0 ? '+' : '') + money(variance)"></dd></div>
                </dl>
                <p class="mt-4 rounded-xl p-3 text-xs font-medium" :class="Math.abs(variance) < 0.01 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                    x-text="Math.abs(variance) < 0.01 ? 'Balanced — safe to mark as Reconciled.' : (variance < 0 ? 'Shortage: collector is holding less than recorded.' : 'Excess: more cash than receipts — check for unrecorded collections.')"></p>
            </div>
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Save changes' : 'Save reconciliation' }}</button>
        </div>
    </form>
</x-layouts.app>
