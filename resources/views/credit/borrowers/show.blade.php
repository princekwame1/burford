@php use App\Support\Money; @endphp
<x-layouts.app :title="$borrower->full_name">
    <x-page-header :title="$borrower->displayName()" :subtitle="$borrower->code.' · '.$borrower->phone.' · '.($borrower->branch?->name ?? 'No branch')" :back="route('credit.borrowers.index')">
        <a href="{{ route('credit.borrowers.edit', $borrower) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
        @if (auth()->user()->hasRole('admin', 'manager'))
            <a href="{{ route('sms.compose', ['to' => $borrower->phone]) }}" class="btn btn-secondary"><x-icon name="message" class="size-4" /> SMS</a>
        @endif
        <a href="{{ route('credit.applications.create', ['borrower' => $borrower->id]) }}" class="btn btn-primary"><x-icon name="file-text" class="size-4" /> New application</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card">
            <div class="card-body flex flex-col items-center text-center">
                @if ($borrower->photo_path)
                    <img src="{{ asset('storage/'.$borrower->photo_path) }}" class="size-24 rounded-2xl object-cover" alt="Passport photo">
                @else
                    <span class="grid size-24 place-items-center rounded-2xl bg-sky-50 font-display text-3xl font-bold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">{{ mb_substr($borrower->full_name, 0, 1) }}</span>
                @endif
                <p class="mt-3 font-display text-lg font-bold text-ink-900 dark:text-white">{{ $borrower->displayName() }}</p>
                <p class="text-sm text-ink-500">{{ $borrower->occupation ?: 'Occupation not set' }}</p>
            </div>
            <dl class="divide-y divide-ink-100 border-t border-ink-100 text-sm dark:divide-ink-800 dark:border-ink-800">
                @foreach ([
                    ['Ghana Card', $borrower->ghana_card], ['Date of birth', $borrower->date_of_birth ? $borrower->date_of_birth->format('d M Y').' ('.$borrower->age().' yrs)' : null],
                    ['Gender', $borrower->gender], ['Marital status', $borrower->marital_status], ['Nationality', $borrower->nationality],
                    ['Address', $borrower->residential_address], ['Digital address', $borrower->digital_address], ['City / district', trim($borrower->city.' / '.$borrower->district, ' /')],
                    ['Email', $borrower->email], ["Father's name", $borrower->father_name], ["Mother's name", $borrower->mother_name],
                    ['Business', trim($borrower->employer_name.' · '.$borrower->business_nature, ' ·')], ['Business location', $borrower->business_location],
                    ['Years in operation', $borrower->years_in_operation], ['Monthly income', $borrower->monthly_income ? Money::format($borrower->monthly_income) : null],
                ] as [$l, $v])
                    <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-ink-500">{{ $l }}</dt><dd class="text-right font-medium text-ink-900 dark:text-white">{{ $v ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </div>
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Loans</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Loan</th><th>Disbursed</th><th class="num">Principal</th><th class="num">Total</th><th class="num">Outstanding</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($borrower->loans as $loan)
                            <tr>
                                <td><a href="{{ route('credit.loans.show', $loan) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $loan->loan_no }}</a></td>
                                <td>{{ $loan->disbursement_date->format('d M Y') }}</td>
                                <td class="num">{{ Money::format($loan->principal) }}</td>
                                <td class="num">{{ Money::format($loan->total_repayment) }}</td>
                                <td class="num font-semibold">{{ Money::format($loan->outstanding()) }}</td>
                                <td><x-badge :status="$loan->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="landmark" title="No loans yet" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Applications</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Application</th><th>Date</th><th>Purpose</th><th class="num">Requested</th><th class="num">Approved</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($borrower->applications as $a)
                            <tr>
                                <td><a href="{{ route('credit.applications.show', $a) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $a->application_no }}</a></td>
                                <td>{{ $a->application_date->format('d M Y') }}</td>
                                <td>{{ $a->purpose }}</td>
                                <td class="num">{{ Money::format($a->amount_requested) }}</td>
                                <td class="num">{{ $a->approved_amount ? Money::format($a->approved_amount) : '—' }}</td>
                                <td><x-badge :status="$a->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="file-text" title="No applications" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
