@php use App\Support\Money; use App\Models\Loan; @endphp
<x-layouts.app title="Loans">
    <x-page-header title="Loan book" subtitle="All disbursed loans with balance, arrears and status.">
        @if (auth()->user()->hasRole('admin', 'manager'))
            <a href="{{ route('reports.export', 'loans') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Export</a>
        @endif
        <a href="{{ route('credit.weekly') }}" class="btn btn-secondary hidden sm:inline-flex"><x-icon name="grid" class="size-4" /> Weekly sheet</a>
        <a href="{{ route('credit.loans.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Direct loan</a>
    </x-page-header>

    <div class="mb-4 inline-flex flex-wrap gap-1 rounded-xl bg-ink-100 p-1 dark:bg-ink-900">
        <a href="{{ route('credit.loans.index', request()->except('status', 'page')) }}" class="tab {{ ! request('status') ? 'active' : '' }}">All</a>
        @foreach (Loan::STATUSES as $s)
            <a href="{{ route('credit.loans.index', ['status' => $s] + request()->except('status', 'page')) }}" class="tab {{ request('status') === $s ? 'active' : '' }}">{{ $s }}</a>
        @endforeach
    </div>

    <div class="card">
        <x-filters :action="route('credit.loans.index')" placeholder="Loan ID, borrower, phone…">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <select name="branch" class="input input-sm sm:w-40">
                <option value="">All branches</option>
                @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
            <select name="officer" class="input input-sm sm:w-40">
                <option value="">All officers</option>
                @foreach ($officers as $o)<option value="{{ $o->id }}" @selected(request('officer') == $o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Loan</th><th>Borrower</th><th>Disbursed</th><th class="num">Principal</th><th>Rate × term</th><th class="num">Total</th><th class="num">Repaid</th><th class="num">Outstanding</th><th>Progress</th><th>Due date</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($loans as $loan)
                    @php $rate = $loan->repaymentRate(); $late = $loan->daysOverdue(); @endphp
                    <tr class="cursor-pointer" onclick="window.location='{{ route('credit.loans.show', $loan) }}'">
                        <td><span class="font-mono text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $loan->loan_no }}</span><div class="text-xs text-ink-400">{{ $loan->branch?->name }}</div></td>
                        <td><span class="font-medium text-ink-900 dark:text-white">{{ $loan->borrower->full_name }}</span><div class="text-xs text-ink-400">{{ $loan->borrower->phone }}</div></td>
                        <td>{{ $loan->disbursement_date->format('d M Y') }}</td>
                        <td class="num">{{ Money::format($loan->principal, false) }}</td>
                        <td class="text-xs">{{ rtrim(rtrim(number_format($loan->interest_rate, 3), '0'), '.') }}% × {{ $loan->term }} {{ strtolower(Loan::periodLabel($loan->frequency)) }}s</td>
                        <td class="num">{{ Money::format($loan->total_repayment, false) }}</td>
                        <td class="num text-emerald-600">{{ Money::format($loan->amount_repaid, false) }}</td>
                        <td class="num font-semibold text-ink-900 dark:text-white">{{ Money::format($loan->outstanding(), false) }}</td>
                        <td style="min-width:110px">
                            <div class="flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $rate * 100 }}%"></div></div><span class="text-xs tabular-nums text-ink-500">{{ round($rate * 100) }}%</span></div>
                        </td>
                        <td>{{ $loan->due_date?->format('d M Y') }}@if($late > 0)<div class="text-xs font-medium text-rose-600">{{ $late }}d late</div>@endif</td>
                        <td><x-badge :status="$loan->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="11"><x-empty icon="landmark" title="No loans found" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($loans->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $loans->links() }}</div>@endif
    </div>
</x-layouts.app>
