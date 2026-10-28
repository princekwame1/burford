@php use App\Models\Loan; $editing = $loan->exists; $locked = $editing && $loan->repayments()->exists(); @endphp
<x-layouts.app :title="$editing ? 'Edit loan' : 'New loan'">
    <x-page-header :title="$editing ? 'Edit loan '.$loan->loan_no : 'Create a direct loan'" :subtitle="$editing ? null : 'For loans not going through the application workflow. Interest is flat: principal × rate × term.'" :back="$editing ? route('credit.loans.show', $loan) : route('credit.loans.index')" />
    @if ($locked)
        <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <x-icon name="alert" class="size-5 shrink-0" /> Repayments have been recorded, so the loan terms are locked. You can still change status, officer, branch and notes.
        </div>
    @endif
    <form method="POST" action="{{ $editing ? route('credit.loans.update', $loan) : route('credit.loans.store') }}"
        x-data="{ p: @js((float) old('principal', $loan->principal ?? 0)), r: @js((float) old('interest_rate', $loan->interest_rate ?? 0)), t: @js((int) old('term', $loan->term ?? 1)),
            get interest() { return Math.round(this.p * this.r / 100 * this.t * 100) / 100 }, get total() { return this.p + this.interest } }"
        class="grid gap-6 lg:grid-cols-3">
        @csrf @if($editing) @method('PUT') @endif
        <fieldset class="card lg:col-span-2">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field label="Loan ID" name="loan_no" :value="$loan->loan_no" required :readonly="$locked" />
                <x-select label="Borrower" name="borrower_id" :value="$loan->borrower_id" :options="$borrowers->mapWithKeys(fn ($b) => [$b->id => $b->full_name.' · '.$b->phone])" placeholder="Select borrower…" required />
                <x-select label="Branch" name="branch_id" :value="$loan->branch_id" :options="$branches->pluck('name', 'id')" placeholder="—" />
                <x-select label="Loan officer" name="officer_id" :value="$loan->officer_id" :options="$officers->pluck('name', 'id')" placeholder="—" />
                <x-field label="Disbursement date" name="disbursement_date" type="date" :value="$loan->disbursement_date" required :readonly="$locked" />
                <x-select label="Disbursement method" name="disbursement_method" :value="$loan->disbursement_method" :options="['Cash', 'Mobile Money', 'Bank']" />
                <div><label class="label">Loan amount (GH₵) *</label><input type="number" step="0.01" name="principal" x-model.number="p" class="input" required @readonly($locked)>@error('principal')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label">Interest rate (% per period) *</label><input type="number" step="0.001" name="interest_rate" x-model.number="r" class="input" required @readonly($locked)></div>
                <div><label class="label">Term (number of periods) *</label><input type="number" step="1" min="1" name="term" x-model.number="t" class="input" required @readonly($locked)></div>
                <x-select label="Frequency" name="frequency" :value="$loan->frequency" :options="Loan::FREQUENCIES" />
                @if ($editing)
                    <x-select label="Status" name="status" :value="$loan->status" :options="Loan::STATUSES" help="Active/Overdue/Closed update automatically; set Defaulted manually." />
                @endif
                <x-field label="Notes" name="notes" type="textarea" :value="$loan->notes" class="sm:col-span-2" />
            </div>
        </fieldset>
        <div class="space-y-4">
            <div class="card card-body">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Loan summary</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Principal</dt><dd class="font-semibold tabular-nums" x-text="money(p)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Interest amount</dt><dd class="font-semibold tabular-nums" x-text="money(interest)"></dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-3 dark:border-ink-800"><dt class="text-ink-500">Total repayment</dt><dd class="font-display text-lg font-bold tabular-nums text-ink-900 dark:text-white" x-text="money(total)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Per installment</dt><dd class="font-semibold tabular-nums" x-text="money(t ? total / t : 0)"></dd></div>
                </dl>
            </div>
            @unless ($editing)
                <label class="card card-body flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="send_sms" value="1" checked class="checkbox mt-0.5">
                    <span class="text-sm"><span class="font-semibold text-ink-900 dark:text-white">SMS repayment plan</span><span class="block text-xs text-ink-500">Borrower gets amount, installment and first due date.</span></span>
                </label>
            @endunless
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Save changes' : 'Create loan' }}</button>
        </div>
    </form>
</x-layouts.app>
