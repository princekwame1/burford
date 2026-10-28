@php use App\Support\Money; @endphp
<x-layouts.app title="Repayments">
    <x-page-header title="Loan repayments" subtitle="Every payment received against a loan.">
        @if (auth()->user()->hasRole('admin', 'manager'))
            <a href="{{ route('reports.export', 'repayments') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Export</a>
        @endif
    </x-page-header>
    <div class="mb-4 max-w-sm"><x-stat label="Total received (filtered)" :value="Money::format($total)" icon="arrow-down-circle" tone="brand" /></div>
    <div class="card">
        <x-filters :action="route('credit.repayments.index')" placeholder="Receipt, loan ID, borrower…">
            <select name="method" class="input input-sm sm:w-36">
                <option value="">Any method</option>
                @foreach (['Cash', 'Mobile Money', 'Bank'] as $m)<option @selected(request('method') === $m)>{{ $m }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input input-sm sm:w-40">
            <input type="date" name="to" value="{{ request('to') }}" class="input input-sm sm:w-40">
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Receipt</th><th>Date</th><th>Loan</th><th>Borrower</th><th>Installment</th><th class="num">Amount</th><th>Method</th><th>Reference</th><th>Received by</th></tr></thead>
                <tbody>
                @forelse ($repayments as $r)
                    <tr>
                        <td class="font-mono text-xs">{{ $r->receipt_no }}</td>
                        <td>{{ $r->date->format('d M Y') }}</td>
                        <td><a href="{{ route('credit.loans.show', $r->loan_id) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $r->loan?->loan_no }}</a></td>
                        <td class="font-medium text-ink-900 dark:text-white">{{ $r->loan?->borrower?->full_name }}</td>
                        <td>{{ $r->period_no ? '#'.$r->period_no : '—' }}</td>
                        <td class="num font-semibold text-emerald-600">{{ Money::format($r->amount) }}</td>
                        <td>{{ $r->payment_method }}</td>
                        <td class="text-xs">{{ $r->reference }}</td>
                        <td>{{ $r->receiver?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty icon="arrow-down-circle" title="No repayments found" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($repayments->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $repayments->links() }}</div>@endif
    </div>
</x-layouts.app>
