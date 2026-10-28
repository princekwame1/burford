@php
    $editing = $collection->exists;
    $customerData = $customers->mapWithKeys(fn ($c) => [$c->id => ['contribution' => (float) $c->contribution, 'collector' => $c->collector_id]]);
@endphp
<x-layouts.app :title="$editing ? 'Edit receipt' : 'New receipt'">
    <x-page-header :title="$editing ? 'Edit receipt '.$collection->receipt_no : 'Record a collection'" subtitle="Outstanding and status are calculated automatically." :back="route('susu.collections.index')" />

    <form method="POST" action="{{ $editing ? route('susu.collections.update', $collection) : route('susu.collections.store') }}"
        x-data="{
            customers: @js($customerData),
            customer: @js((string) old('susu_customer_id', $collection->susu_customer_id)),
            collector: @js((string) old('collector_id', $collection->collector_id)),
            expected: @js((float) old('expected', $collection->expected ?? 0)),
            collected: @js((float) old('collected', $collection->collected ?? 0)),
            pick() { const c = this.customers[this.customer]; if (c) { this.expected = c.contribution; if (!this.collected) this.collected = c.contribution; if (c.collector) this.collector = String(c.collector); } },
            get outstanding() { return Math.max(this.expected - this.collected, 0) },
            get status() { return this.outstanding === 0 ? 'Paid' : (this.collected > 0 ? 'Partial' : 'Unpaid') },
        }" @if (! $editing && ! old('expected')) x-init="pick()" @endif class="grid gap-6 lg:grid-cols-3">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card lg:col-span-2">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field label="Receipt no." name="receipt_no" :value="$collection->receipt_no" required />
                <x-field label="Date" name="date" type="date" :value="$collection->date" required />
                <div class="sm:col-span-2">
                    <label class="label">Customer <span class="text-rose-500">*</span></label>
                    <select name="susu_customer_id" x-model="customer" @change="pick()" required class="input">
                        <option value="">Select customer…</option>
                        @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }} ({{ number_format($c->contribution, 2) }})</option>@endforeach
                    </select>
                    @error('susu_customer_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                @unless (auth()->user()->role === 'collector')
                    <div>
                        <label class="label">Collector</label>
                        <select name="collector_id" x-model="collector" class="input">
                            <option value="">— Customer's collector —</option>
                            @foreach ($collectors as $col)<option value="{{ $col->id }}">{{ $col->name }}</option>@endforeach
                        </select>
                    </div>
                @endunless
                <x-select label="Payment method" name="payment_method" :value="$collection->payment_method" :options="['Cash', 'Mobile Money', 'Bank']" />
                <div>
                    <label class="label">Expected <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="expected" x-model.number="expected" class="input" required>
                    @error('expected')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Collected <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="collected" x-model.number="collected" class="input" required>
                    @error('collected')<p class="error">{{ $message }}</p>@enderror
                </div>
                <x-field label="Verified by" name="verified_by" :value="$collection->verified_by" />
                <x-field label="Deposit ref." name="deposit_ref" :value="$collection->deposit_ref" />
                <x-field label="Notes" name="notes" type="textarea" :value="$collection->notes" class="sm:col-span-2" />
            </div>
        </div>
        <div class="space-y-4">
            <div class="card card-body">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Preview</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Expected</dt><dd class="font-semibold tabular-nums" x-text="money(expected)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Collected</dt><dd class="font-semibold tabular-nums text-emerald-600" x-text="money(collected)"></dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-3 dark:border-ink-800"><dt class="text-ink-500">Outstanding</dt><dd class="font-bold tabular-nums" :class="outstanding > 0 ? 'text-amber-600' : ''" x-text="money(outstanding)"></dd></div>
                </dl>
                <div class="mt-4 rounded-xl p-3 text-center text-sm font-semibold"
                    :class="{ 'bg-emerald-50 text-emerald-700': status === 'Paid', 'bg-amber-50 text-amber-700': status === 'Partial', 'bg-rose-50 text-rose-700': status === 'Unpaid' }" x-text="status"></div>
            </div>
            @unless ($editing)
                <label class="card card-body flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="send_sms" value="1" checked class="checkbox mt-0.5">
                    <span class="text-sm"><span class="font-semibold text-ink-900 dark:text-white">Send SMS receipt</span><span class="block text-xs text-ink-500">Customer receives amount and new balance via TextTango.</span></span>
                </label>
            @endunless
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Save changes' : 'Save receipt' }}</button>
            @unless ($editing)
                <button name="after" value="new" class="btn btn-secondary w-full">Save & record another</button>
            @endunless
        </div>
    </form>
</x-layouts.app>
