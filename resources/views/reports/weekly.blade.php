@php use App\Support\Money; @endphp
<x-layouts.app title="Weekly repayment sheet">
    <x-page-header title="Repayment sheet" subtitle="Amount paid against each installment. Green = paid, amber = partial, red = missed, grey = upcoming." :back="route('credit.loans.index')">
        <form method="GET" class="flex gap-2">
            <select name="status" class="input input-sm w-40" onchange="this.form.submit()">
                <option value="">Open loans</option>
                @foreach (\App\Models\Loan::STATUSES as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </form>
        <button onclick="window.print()" class="btn btn-secondary btn-sm"><x-icon name="printer" class="size-3.5" /> Print</button>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-ink-50 dark:bg-ink-900">SN</th>
                        <th class="sticky left-10 z-10 bg-ink-50 dark:bg-ink-900">Name</th>
                        <th>Loan ID</th>
                        @for ($i = 1; $i <= $maxTerm; $i++)<th class="num">WK{{ $i }}</th>@endfor
                        <th class="num">Repaid</th><th class="num">Balance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($loans as $loan)
                    @php $sched = $loan->schedule()->keyBy('period'); @endphp
                    <tr>
                        <td class="sticky left-0 bg-white dark:bg-ink-900">{{ $loop->iteration }}</td>
                        <td class="sticky left-10 bg-white font-medium text-ink-900 dark:bg-ink-900 dark:text-white"><a href="{{ route('credit.loans.show', $loan) }}">{{ $loan->borrower->full_name }}</a></td>
                        <td class="font-mono text-xs">{{ $loan->loan_no }}</td>
                        @for ($i = 1; $i <= $maxTerm; $i++)
                            @php $r = $sched->get($i); @endphp
                            <td class="num p-1">
                                @if ($r)
                                    @php $c = ['Paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300', 'Partial' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', 'Missed' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300', 'Upcoming' => 'text-ink-300'][$r['status']]; @endphp
                                    <span class="block rounded-md px-1.5 py-1 text-xs tabular-nums {{ $c }}" title="Due {{ $r['due_date']->format('d M') }} · {{ $r['status'] }}">{{ $r['paid'] > 0 ? number_format($r['paid'], 0) : '·' }}</span>
                                @endif
                            </td>
                        @endfor
                        <td class="num font-semibold text-emerald-600">{{ Money::format($loan->amount_repaid, false) }}</td>
                        <td class="num font-semibold">{{ Money::format($loan->outstanding(), false) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $maxTerm + 5 }}"><x-empty icon="grid" title="No loans to show" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
