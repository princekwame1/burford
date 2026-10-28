@php
    use App\Support\Money;
    use App\Models\Loan;
    $period = Loan::periodLabel($loan->frequency);
    $rate = $loan->repaymentRate();
    $isManager = auth()->user()->hasRole('admin', 'manager');
@endphp
<x-layouts.app :title="$loan->loan_no">
    <x-page-header :title="$loan->borrower->full_name" :subtitle="$loan->loan_no.' · '.$loan->branch?->name.' · Officer: '.($loan->officer?->name ?? '—')" :back="route('credit.loans.index')">
        <x-badge :status="$loan->status" class="px-3 py-1 text-sm" />
        <a href="{{ route('credit.loans.statement', $loan) }}" class="btn btn-secondary"><x-icon name="printer" class="size-4" /> Statement</a>
        @if ($loan->status !== 'Closed')
            <form method="POST" action="{{ route('credit.loans.remind', $loan) }}"
                data-confirm-title="Send SMS reminder?" data-confirm="{{ $loan->borrower->full_name }} ({{ $loan->borrower->phone }}) will get a reminder of their balance and next payment." data-confirm-button="Send reminder">@csrf
                <button class="btn btn-secondary"><x-icon name="send" class="size-4" /> SMS reminder</button>
            </form>
        @endif
        <a href="{{ route('credit.loans.edit', $loan) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Loan amount" :value="Money::format($loan->principal)" icon="banknote" tone="brand" :sub="'Disbursed '.$loan->disbursement_date->format('d M Y').' via '.$loan->disbursement_method" />
        <x-stat label="Total repayment" :value="Money::format($loan->total_repayment)" icon="receipt" tone="blue" :sub="'Interest '.Money::format($loan->interest_amount).' ('.rtrim(rtrim(number_format($loan->interest_rate, 3), '0'), '.').'% × '.$loan->term.')'" />
        <x-stat label="Outstanding balance" :value="Money::format($loan->outstanding())" icon="wallet" tone="violet">
            <div class="flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $rate * 100 }}%"></div></div><span class="tabular-nums">{{ round($rate * 100) }}% repaid</span></div>
        </x-stat>
        <x-stat label="Arrears" :value="Money::format($loan->arrears())" icon="alert" :tone="$loan->arrears() > 0.009 ? 'rose' : 'brand'" :sub="$loan->daysOverdue() > 0 ? $loan->daysOverdue().' days overdue' : 'Up to date'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div><h3 class="card-title">{{ $loan->frequency }} repayment schedule</h3><p class="text-xs text-ink-500">{{ $loan->term }} installments of {{ Money::format($loan->installment) }} · final due {{ $loan->due_date?->format('d M Y') }}</p></div>
            </div>
            {{-- Visual strip like the WK1…WKn columns of the weekly repayment sheet --}}
            <div class="flex flex-wrap gap-1.5 border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                @foreach ($schedule as $row)
                    @php $c = ['Paid' => 'bg-emerald-500 text-white', 'Partial' => 'bg-amber-400 text-amber-950', 'Missed' => 'bg-rose-500 text-white', 'Upcoming' => 'bg-ink-100 text-ink-500 dark:bg-ink-800'][$row['status']]; @endphp
                    <span class="grid h-8 min-w-10 place-items-center rounded-lg px-2 text-[11px] font-bold {{ $c }}" title="{{ $row['status'] }} · due {{ $row['due_date']->format('d M') }} · paid {{ number_format($row['paid'], 2) }}">{{ substr($period, 0, 1) }}{{ $row['period'] }}</span>
                @endforeach
            </div>
            <div class="table-wrap max-h-[460px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>#</th><th>Due date</th><th class="num">Installment</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($schedule as $row)
                        <tr class="{{ $next && $next['period'] === $row['period'] ? 'bg-brand-50/50 dark:bg-brand-500/5' : '' }}">
                            <td class="font-semibold">{{ substr($period, 0, 1) }}{{ $row['period'] }}</td>
                            <td>{{ $row['due_date']->format('D, d M Y') }}</td>
                            <td class="num">{{ Money::format($row['amount'], false) }}</td>
                            <td class="num text-emerald-600">{{ $row['paid'] > 0 ? Money::format($row['paid'], false) : '' }}</td>
                            <td class="num">{{ Money::format($row['balance'], false) }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="2">Total</td><td class="num">{{ Money::format($loan->total_repayment, false) }}</td><td class="num">{{ Money::format($loan->amount_repaid, false) }}</td><td class="num">{{ Money::format($loan->outstanding(), false) }}</td><td></td></tr></tfoot>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            @if ($loan->status !== 'Closed')
                <form method="POST" action="{{ route('credit.repayments.store', $loan) }}" class="card border-brand-200 dark:border-brand-500/30"
                    x-data="{ amount: @js((float) old('amount', $next ? $next['balance'] : 0) ?: '') }"
                    data-confirm-title="Record this repayment?" :data-confirm="money(amount) + @js(' from '.$loan->borrower->full_name.' against '.$loan->loan_no.'.')" data-confirm-button="Save repayment">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Record repayment</h3>
                        @if ($next)<span class="text-xs text-ink-500">Next: {{ Money::format($next['balance']) }} due {{ $next['due_date']->format('d M') }}</span>@endif
                    </div>
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <x-field label="Date" name="date" type="date" :value="today()" required />
                            <x-field label="Amount" name="amount" type="number" x-model.number="amount" required />
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @if ($loan->arrears() > 0.009)
                                <button type="button" x-on:click="amount = {{ round($loan->arrears(), 2) }}" class="btn btn-secondary btn-sm">Arrears {{ number_format($loan->arrears(), 2) }}</button>
                            @endif
                            <button type="button" x-on:click="amount = {{ round($loan->outstanding(), 2) }}" class="btn btn-secondary btn-sm">Pay off {{ number_format($loan->outstanding(), 2) }}</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <x-select label="Method" name="payment_method" value="Cash" :options="['Cash', 'Mobile Money', 'Bank']" />
                            <x-field label="Reference" name="reference" placeholder="MoMo txn ID" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300"><input type="checkbox" name="send_sms" value="1" checked class="checkbox"> SMS receipt to borrower</label>
                        <button class="btn btn-primary w-full"><x-icon name="check" class="size-4" /> Save repayment</button>
                    </div>
                </form>
            @else
                <div class="card card-body flex items-center gap-3 border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                    <x-icon name="check-circle" /> <span class="text-sm font-semibold">This loan is fully repaid and closed.</span>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h3 class="card-title">Payments received</h3><span class="text-xs text-ink-500">{{ $loan->repayments->count() }}</span></div>
                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($loan->repayments->sortByDesc('date') as $r)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                            <span class="grid size-8 place-items-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10"><x-icon name="arrow-down-circle" class="size-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink-900 tabular-nums dark:text-white">{{ Money::format($r->amount) }}</p>
                                <p class="text-xs text-ink-500">{{ $r->date->format('d M Y') }} · {{ $r->payment_method }} · {{ $r->receipt_no }}</p>
                            </div>
                            @if ($isManager)<x-delete-button :action="route('credit.repayments.destroy', $r)" title="Reverse this repayment?" confirm="The loan balance will be recalculated." button="Yes, reverse" />@endif
                        </li>
                    @empty
                        <li><x-empty icon="arrow-down-circle" title="No payments yet" /></li>
                    @endforelse
                </ul>
            </div>

            @if ($sms->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">SMS history</h3></div>
                    <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                        @foreach ($sms as $m)
                            <li class="px-5 py-3 text-xs">
                                <div class="flex items-center justify-between"><span class="font-semibold uppercase tracking-wide text-ink-500">{{ str_replace('_', ' ', $m->type) }}</span><x-badge :status="$m->status" /></div>
                                <p class="mt-1 text-ink-600 dark:text-ink-300">{{ \Illuminate\Support\Str::limit($m->message, 110) }}</p>
                                <p class="mt-1 text-ink-400">{{ $m->created_at->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
