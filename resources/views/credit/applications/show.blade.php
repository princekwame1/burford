@php
    use App\Support\Money;
    use App\Models\Loan;
    $a = $application;
    $b = $a->borrower;
    $isManager = auth()->user()->hasRole('admin', 'manager');
@endphp
<x-layouts.app :title="$a->application_no">
    <x-page-header :title="$b->displayName()" :subtitle="$a->application_no.' · applied '.$a->application_date->format('d M Y').' · '.($a->branch?->name ?? 'No branch')" :back="route('credit.applications.index')">
        <x-badge :status="$a->status" class="px-3 py-1 text-sm" />
        <a href="{{ route('credit.applications.print', $a) }}" class="btn btn-secondary"><x-icon name="printer" class="size-4" /> Print form</a>
        @if ($a->status !== 'Disbursed')
            <a href="{{ route('credit.applications.edit', $a) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
        @endif
        @if ($a->loan)
            <a href="{{ route('credit.loans.show', $a->loan) }}" class="btn btn-primary"><x-icon name="landmark" class="size-4" /> View loan {{ $a->loan->loan_no }}</a>
        @endif
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Amount requested" :value="Money::format($a->amount_requested)" icon="banknote" tone="brand" />
                <x-stat label="Preferred term" :value="$a->preferred_term ? $a->preferred_term.' '.strtolower(Loan::periodLabel($a->repayment_frequency)).'s' : '—'" icon="calendar" tone="blue" :sub="$a->repayment_frequency.' repayments'" />
                <x-stat label="Monthly income" :value="$b->monthly_income ? Money::format($b->monthly_income) : '—'" icon="wallet" tone="violet" :sub="'Existing obligations '.Money::format($a->totalObligations()).'/mo'" />
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Applicant & business</h3><a href="{{ route('credit.borrowers.show', $b) }}" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">Full profile</a></div>
                <dl class="grid gap-x-8 gap-y-3 p-5 text-sm sm:grid-cols-2">
                    @foreach ([
                        ['Phone', $b->phone], ['Ghana Card', $b->ghana_card], ['Date of birth', $b->date_of_birth?->format('d M Y')], ['Gender / status', trim($b->gender.' · '.$b->marital_status, ' ·')],
                        ['Address', $b->residential_address], ['Digital address', $b->digital_address], ['Occupation', $b->occupation], ['Business', trim($b->employer_name.' · '.$b->business_nature, ' ·')],
                        ['Business location', $b->business_location], ['Years in operation', $b->years_in_operation],
                        ['Purpose', $a->purpose === 'Other' ? $a->purpose_other : $a->purpose], ['Preferred disbursement', $a->preferred_disbursement_date?->format('d M Y')],
                        ['Repayment start', $a->proposed_repayment_start?->format('d M Y')], ['Credit officer', $a->officer?->name],
                    ] as [$l, $v])
                        <div class="flex justify-between gap-4 border-b border-ink-100 pb-2 dark:border-ink-800"><dt class="text-ink-500">{{ $l }}</dt><dd class="text-right font-medium text-ink-900 dark:text-white">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Family members</h3></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Name</th><th>Age</th><th>Relationship</th><th>Occupation</th></tr></thead>
                            <tbody>
                            @forelse ($a->family_members ?? [] as $f)
                                <tr><td>{{ $f['name'] }}</td><td>{{ $f['age'] ?? '' }}</td><td>{{ $f['relationship'] ?? '' }}</td><td>{{ $f['occupation'] ?? '' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-ink-400">None listed</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Existing obligations</h3></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Lender</th><th class="num">Balance</th><th class="num">Monthly</th></tr></thead>
                            <tbody>
                            @forelse ($a->existing_obligations ?? [] as $o)
                                <tr><td>{{ $o['lender'] }}</td><td class="num">{{ Money::format($o['balance'] ?? 0, false) }}</td><td class="num">{{ Money::format($o['monthly_repayment'] ?? 0, false) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-ink-400">None declared</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <p class="text-sm font-semibold text-ink-900 dark:text-white">Guarantor</p>
                <p class="mt-1 text-sm text-ink-600 dark:text-ink-300">{{ $a->guarantor_name ?: 'No guarantor' }} @if($a->guarantor_relationship) · {{ $a->guarantor_relationship }} @endif @if($a->guarantor_phone) · {{ $a->guarantor_phone }} @endif @if($a->guarantor_address) · {{ $a->guarantor_address }} @endif</p>
                <p class="mt-3 flex items-center gap-2 text-xs {{ $a->declaration_accepted ? 'text-emerald-600' : 'text-rose-600' }}"><x-icon :name="$a->declaration_accepted ? 'check-circle' : 'alert'" class="size-4" /> Declaration {{ $a->declaration_accepted ? 'accepted' : 'not accepted' }}</p>
            </div>
        </div>

        {{-- For official use only --}}
        <div class="space-y-6">
            @if ($isManager && in_array($a->status, ['Pending', 'Approved', 'Declined']))
                <form method="POST" action="{{ route('credit.applications.decide', $a) }}" class="card"
                    x-data="{ decision: @js(old('decision', $a->status === 'Pending' ? 'Approved' : $a->status)), amount: @js((float) old('approved_amount', $a->approved_amount ?? $a->amount_requested)),
                        rate: @js((float) old('interest_rate', $a->interest_rate ?? $defaultRate)), term: @js((int) old('approved_term', $a->approved_term ?? $a->preferred_term ?? 12)), freq: @js(old('approved_frequency', $a->approved_frequency ?? $a->repayment_frequency)),
                        get interest() { return Math.round(this.amount * this.rate / 100 * this.term * 100) / 100 }, get total() { return this.amount + this.interest }, get inst() { return this.term ? this.total / this.term : 0 } }"
                    :data-confirm="decision === 'Declined' ? 'The application will be marked as declined.' : (decision === 'Approved' ? `${money(amount)} at ${rate}% × ${term} (${freq}). Total payable ${money(total)}, ${money(inst)} per installment.` : null)"
                    :data-confirm-title="decision === 'Declined' ? 'Decline this application?' : 'Approve this application?'"
                    :data-confirm-button="decision === 'Declined' ? 'Yes, decline' : 'Yes, approve'"
                    :data-confirm-tone="decision === 'Declined' ? 'danger' : 'primary'">
                    @csrf
                    <div class="card-header"><h3 class="card-title">For official use only</h3></div>
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-3 gap-1 rounded-xl bg-ink-100 p-1 dark:bg-ink-800">
                            @foreach (['Approved' => 'Approve', 'Pending' => 'Pending', 'Declined' => 'Decline'] as $v => $l)
                                <label class="cursor-pointer"><input type="radio" name="decision" value="{{ $v }}" x-model="decision" class="peer sr-only">
                                    <span class="block rounded-lg py-1.5 text-center text-sm font-medium text-ink-500 peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm dark:peer-checked:bg-ink-900 dark:peer-checked:text-white">{{ $l }}</span></label>
                            @endforeach
                        </div>
                        <div x-show="decision === 'Approved'" class="space-y-4">
                            <div><label class="label">Approved amount (GH₵)</label><input type="number" step="0.01" name="approved_amount" x-model.number="amount" class="input"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="label">Interest % per period</label><input type="number" step="0.001" name="interest_rate" x-model.number="rate" class="input"></div>
                                <div><label class="label">Credit term</label><input type="number" step="1" name="approved_term" x-model.number="term" class="input"></div>
                            </div>
                            <div><label class="label">Repayment frequency</label>
                                <select name="approved_frequency" x-model="freq" class="input">@foreach (Loan::FREQUENCIES as $f)<option>{{ $f }}</option>@endforeach</select></div>
                            <dl class="space-y-2 rounded-xl bg-ink-50 p-4 text-sm dark:bg-ink-800/60">
                                <div class="flex justify-between"><dt class="text-ink-500">Interest / service charge</dt><dd class="font-semibold tabular-nums" x-text="money(interest)"></dd></div>
                                <div class="flex justify-between"><dt class="text-ink-500">Total amount payable</dt><dd class="font-bold tabular-nums text-ink-900 dark:text-white" x-text="money(total)"></dd></div>
                                <div class="flex justify-between"><dt class="text-ink-500">Each installment</dt><dd class="font-semibold tabular-nums" x-text="money(inst)"></dd></div>
                            </dl>
                            <p class="text-xs text-ink-500">Flat rate: amount × rate × term, as in the loan book.</p>
                        </div>
                        <x-field label="Notes" name="decision_notes" type="textarea" :value="$a->decision_notes" />
                        <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300"><input type="checkbox" name="send_sms" value="1" checked class="checkbox"> Notify applicant by SMS</label>
                        @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
                        <button class="btn btn-dark w-full">Save decision</button>
                    </div>
                </form>
            @endif

            @if ($a->status !== 'Pending')
                <div class="card card-body text-sm">
                    <p class="font-semibold text-ink-900 dark:text-white">Decision</p>
                    <p class="mt-1 text-ink-500">{{ $a->status }} by {{ $a->approver?->name ?? '—' }} {{ $a->decided_at ? 'on '.$a->decided_at->format('d M Y H:i') : '' }}</p>
                    @if ($a->approved_amount)
                        @php $t = $a->approvedTotals(); @endphp
                        <p class="mt-2 text-ink-700 dark:text-ink-300">{{ Money::format($a->approved_amount) }} at {{ rtrim(rtrim(number_format($a->interest_rate, 3), '0'), '.') }}% × {{ $a->approved_term }} ({{ $a->approved_frequency }}) → <strong>{{ Money::format($t['total']) }}</strong></p>
                    @endif
                    @if ($a->decision_notes)<p class="mt-2 rounded-lg bg-ink-50 p-2 text-ink-600 dark:bg-ink-800">{{ $a->decision_notes }}</p>@endif
                </div>
            @endif

            @if ($isManager && $a->status === 'Approved')
                <div class="card card-body border-brand-200 dark:border-brand-500/30">
                    <p class="text-sm font-semibold text-ink-900 dark:text-white">Ready to disburse</p>
                    <p class="mt-1 text-sm text-ink-500">Approved for {{ Money::format($a->approved_amount) }}. Disbursing creates the loan and its repayment schedule.</p>
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'disburse')" class="btn btn-primary mt-4 w-full"><x-icon name="banknote" class="size-4" /> Disburse loan</button>
                </div>

                <x-modal name="disburse" title="Disburse loan" :subtitle="$b->displayName().' · '.Money::format($a->approved_amount)"
                    :show="$errors->hasAny(['disbursement_date', 'disbursement_method', 'notes'])">
                    <form method="POST" action="{{ route('credit.applications.disburse', $a) }}"
                        data-confirm-title="Disburse {{ Money::format($a->approved_amount) }}?" data-confirm="A loan will be opened for {{ $b->displayName() }} and the repayment schedule will start. This cannot be undone." data-confirm-button="Yes, disburse">
                        @csrf
                        <div class="card-body space-y-4">
                            <x-field label="Disbursement date" name="disbursement_date" type="date" :value="today()" required />
                            <x-select label="Method" name="disbursement_method" value="Cash" :options="['Cash', 'Mobile Money', 'Bank']" />
                            <x-field label="Notes" name="notes" type="textarea" />
                            <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300"><input type="checkbox" name="send_sms" value="1" checked class="checkbox"> SMS repayment plan to borrower</label>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-ink-100 px-5 py-4 dark:border-ink-800">
                            <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancel</button>
                            <button class="btn btn-primary"><x-icon name="banknote" class="size-4" /> Disburse {{ Money::format($a->approved_amount) }}</button>
                        </div>
                    </form>
                </x-modal>
            @endif

            @if ($b->loans->isNotEmpty())
                <div class="card card-body">
                    <p class="text-sm font-semibold text-ink-900 dark:text-white">Credit history</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($b->loans as $l)
                            <li class="flex items-center justify-between"><a href="{{ route('credit.loans.show', $l) }}" class="font-mono text-xs text-brand-700 dark:text-brand-300">{{ $l->loan_no }}</a><span class="tabular-nums">{{ Money::format($l->principal) }}</span><x-badge :status="$l->status" /></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($isManager && $a->status !== 'Disbursed')
                <x-delete-button :action="route('credit.applications.destroy', $a)" title="Delete this application?" label="Delete application" class="btn btn-danger-soft w-full" />
            @endif
        </div>
    </div>
</x-layouts.app>
