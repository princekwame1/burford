@php
    use App\Models\LoanApplication;
    use App\Models\Loan;
    $editing = $application->exists;
    $family = old('family_members', $application->family_members ?: [['name' => '', 'age' => '', 'relationship' => '', 'occupation' => '']]);
    $obligations = old('existing_obligations', $application->existing_obligations ?: [['lender' => '', 'balance' => '', 'monthly_repayment' => '']]);
@endphp
<x-layouts.app :title="$editing ? 'Edit application' : 'New loan application'">
    <x-page-header :title="$editing ? 'Edit application '.$application->application_no : 'Credit application'" subtitle="Small credit • Big dreams • Brighter futures" :back="route('credit.applications.index')" />

    @unless ($editing)
        <div class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300"><x-icon name="user" class="size-4" /></span>
                <div><p class="text-sm font-semibold text-ink-900 dark:text-white">Applicant</p><p class="text-xs text-ink-500">Pick a returning borrower to pre-fill their details, or fill in a new one below.</p></div>
            </div>
            <select class="input sm:ml-auto sm:w-80" onchange="window.location='{{ route('credit.applications.create') }}' + (this.value ? '?borrower=' + this.value : '')">
                <option value="">+ New applicant</option>
                @foreach ($borrowers as $b)<option value="{{ $b->id }}" @selected($borrower->id === $b->id)>{{ $b->full_name }} · {{ $b->phone }}</option>@endforeach
            </select>
        </div>
    @endunless

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('credit.applications.update', $application) : route('credit.applications.store') }}" class="space-y-6">
        @csrf @if($editing) @method('PUT') @endif
        @if ($borrower->exists)<input type="hidden" name="borrower_id" value="{{ $borrower->id }}">@endif

        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field label="Application no." name="application_no" :value="$application->application_no" required />
                <x-field label="Date of application" name="application_date" type="date" :value="$application->application_date" required />
                <x-select label="Branch / location" name="branch_id" :value="$application->branch_id" :options="$branches->pluck('name', 'id')" placeholder="—" />
                <x-select label="Credit officer" name="officer_id" :value="$application->officer_id" :options="$officers->pluck('name', 'id')" placeholder="—" />
            </div>
        </div>

        @include('credit.borrowers._fields')

        {{-- C. Credit request --}}
        <div class="card" x-data="{ purpose: @js(old('purpose', $application->purpose)) }">
            <div class="card-header"><h3 class="card-title flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-brand-600 text-xs font-bold text-white">C</span> Credit request</h3></div>
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Amount requested" name="amount_requested" type="number" :value="$application->amount_requested" prefix="GH₵" required />
                <div>
                    <label class="label">Purpose</label>
                    <select name="purpose" x-model="purpose" class="input">
                        <option value="">—</option>
                        @foreach (LoanApplication::PURPOSES as $p)<option>{{ $p }}</option>@endforeach
                    </select>
                </div>
                <div x-show="purpose === 'Other'" x-cloak><x-field label="Other purpose" name="purpose_other" :value="$application->purpose_other" /></div>
                <x-field label="Preferred loan term" name="preferred_term" type="number" step="1" :value="$application->preferred_term" help="Number of repayment periods" />
                <x-select label="Repayment frequency" name="repayment_frequency" :value="$application->repayment_frequency" :options="Loan::FREQUENCIES" />
                <x-field label="Preferred disbursement date" name="preferred_disbursement_date" type="date" :value="$application->preferred_disbursement_date" />
                <x-field label="Proposed repayment start" name="proposed_repayment_start" type="date" :value="$application->proposed_repayment_start" />
            </div>
        </div>

        {{-- D. Supporting information --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-brand-600 text-xs font-bold text-white">D</span> Supporting information</h3></div>
            <div class="card-body space-y-8">
                <div x-data="{ rows: @js(array_values($family)) }">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold text-ink-900 dark:text-white">1. Other family members</p>
                        <button type="button" @click="rows.push({name:'',age:'',relationship:'',occupation:''})" class="btn btn-ghost btn-sm"><x-icon name="plus" class="size-3.5" /> Add</button>
                    </div>
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="mb-2 grid gap-2 sm:grid-cols-[2fr_80px_1fr_1.5fr_auto]">
                            <input :name="`family_members[${i}][name]`" x-model="row.name" placeholder="Name" class="input input-sm">
                            <input :name="`family_members[${i}][age]`" x-model="row.age" type="number" placeholder="Age" class="input input-sm">
                            <input :name="`family_members[${i}][relationship]`" x-model="row.relationship" placeholder="Relationship" class="input input-sm">
                            <input :name="`family_members[${i}][occupation]`" x-model="row.occupation" placeholder="Occupation / profession" class="input input-sm">
                            <button type="button" @click="rows.splice(i, 1)" class="btn btn-danger-soft btn-sm"><x-icon name="x" class="size-3.5" /></button>
                        </div>
                    </template>
                </div>
                <div x-data="{ rows: @js(array_values($obligations)) }">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold text-ink-900 dark:text-white">2. Existing credit(s) / financial obligations</p>
                        <button type="button" @click="rows.push({lender:'',balance:'',monthly_repayment:''})" class="btn btn-ghost btn-sm"><x-icon name="plus" class="size-3.5" /> Add</button>
                    </div>
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="mb-2 grid gap-2 sm:grid-cols-[2fr_1fr_1fr_auto]">
                            <input :name="`existing_obligations[${i}][lender]`" x-model="row.lender" placeholder="Lender / institution" class="input input-sm">
                            <input :name="`existing_obligations[${i}][balance]`" x-model="row.balance" type="number" step="0.01" placeholder="Balance (GH₵)" class="input input-sm">
                            <input :name="`existing_obligations[${i}][monthly_repayment]`" x-model="row.monthly_repayment" type="number" step="0.01" placeholder="Monthly repayment (GH₵)" class="input input-sm">
                            <button type="button" @click="rows.splice(i, 1)" class="btn btn-danger-soft btn-sm"><x-icon name="x" class="size-3.5" /></button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- E. Declaration --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-brand-600 text-xs font-bold text-white">E</span> Declaration & guarantor</h3></div>
            <div class="card-body space-y-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-field label="Guarantor name" name="guarantor_name" :value="$application->guarantor_name" />
                    <x-field label="Guarantor phone" name="guarantor_phone" :value="$application->guarantor_phone" />
                    <x-field label="Relationship" name="guarantor_relationship" :value="$application->guarantor_relationship" />
                    <x-field label="Guarantor address" name="guarantor_address" :value="$application->guarantor_address" />
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-ink-200 bg-ink-50/60 p-4 dark:border-ink-700 dark:bg-ink-800/40">
                    <input type="checkbox" name="declaration_accepted" value="1" class="checkbox mt-0.5" @checked(old('declaration_accepted', $application->declaration_accepted))>
                    <span class="text-sm text-ink-700 dark:text-ink-300">I declare that the information provided in this application is true and complete to the best of my knowledge. I authorise {{ \App\Models\Setting::get('company_name', 'BURFORD Collection') }} to verify the information supplied and conduct appropriate credit and identity checks for assessing this application.</span>
                </label>
                @error('declaration_accepted')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('credit.applications.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary px-6">{{ $editing ? 'Save changes' : 'Submit application' }}</button>
        </div>
    </form>
</x-layouts.app>
