<x-layouts.app title="New withdrawal">
    <x-page-header title="Record a withdrawal" subtitle="The amount plus any commission cannot exceed the customer's available balance." :back="route('susu.withdrawals.index')" />
    <form method="POST" action="{{ route('susu.withdrawals.store') }}"
        x-data="{ customers: @js($customers->keyBy('id')), customer: @js((string) old('susu_customer_id', $selected)), amount: @js((float) old('amount', 0)), commission: @js((float) old('commission', 0)),
            get balance() { return this.customers[this.customer]?.balance ?? 0 }, get after() { return this.balance - this.amount - this.commission } }"
        class="grid gap-6 lg:grid-cols-3"
        data-confirm-title="Record this withdrawal?" data-confirm-button="Record withdrawal"
        :data-confirm="`Pay out ${money(amount)}${commission ? ' + ' + money(commission) + ' commission' : ''} to ${customers[customer]?.label}. Balance after: ${money(after)}.`">
        @csrf
        <div class="card lg:col-span-2">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field label="Withdrawal no." name="withdrawal_no" :value="$withdrawalNo" required />
                <x-field label="Date" name="date" type="date" :value="today()" required />
                <div class="sm:col-span-2">
                    <label class="label">Customer <span class="text-rose-500">*</span></label>
                    <select name="susu_customer_id" x-model="customer" class="input" required>
                        <option value="">Select customer…</option>
                        @foreach ($customers as $c)<option value="{{ $c['id'] }}">{{ $c['label'] }} — bal. {{ number_format($c['balance'], 2) }}</option>@endforeach
                    </select>
                    @error('susu_customer_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" x-model.number="amount" class="input @error('amount') input-error @enderror" required>
                    @error('amount')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Commission / service charge</label>
                    <input type="number" step="0.01" min="0" name="commission" x-model.number="commission" class="input">
                    <p class="help">Collector's fee retained by the company, if any.</p>
                </div>
                <x-select label="Type" name="type" value="Normal" :options="['Normal', 'Emergency', 'Closure']" help="Closure also closes the account." />
                <x-select label="Payment method" name="payment_method" value="Cash" :options="['Cash', 'Mobile Money', 'Bank']" />
                <x-field label="Approved by" name="approved_by" :value="auth()->user()->name" />
                <x-field label="Notes" name="notes" type="textarea" class="sm:col-span-2" />
            </div>
        </div>
        <div class="space-y-4">
            <div class="card card-body">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Balance check</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Available</dt><dd class="font-semibold tabular-nums" x-text="money(balance)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Withdrawal + commission</dt><dd class="font-semibold tabular-nums text-rose-600" x-text="'− ' + money(amount + commission)"></dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-3 dark:border-ink-800"><dt class="text-ink-500">Balance after</dt><dd class="font-bold tabular-nums" :class="after < 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="money(after)"></dd></div>
                </dl>
                <p x-show="after < 0" x-cloak class="mt-4 rounded-xl bg-rose-50 p-3 text-xs font-medium text-rose-700">Insufficient balance for this withdrawal.</p>
            </div>
            <label class="card card-body flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="send_sms" value="1" checked class="checkbox mt-0.5">
                <span class="text-sm"><span class="font-semibold text-ink-900 dark:text-white">Notify customer by SMS</span><span class="block text-xs text-ink-500">Confirms the payout and remaining balance.</span></span>
            </label>
            <button class="btn btn-primary w-full py-3" :disabled="after < 0 || !customer">Record withdrawal</button>
        </div>
    </form>
</x-layouts.app>
