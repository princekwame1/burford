@php use App\Support\Money; @endphp
<x-layouts.app title="Loan applications">
    <x-page-header title="Loan applications" subtitle="Review, approve and disburse credit requests.">
        <a href="{{ route('credit.applications.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New application</a>
    </x-page-header>

    <div class="mb-4 inline-flex flex-wrap gap-1 rounded-xl bg-ink-100 p-1 dark:bg-ink-900">
        <a href="{{ route('credit.applications.index') }}" class="tab {{ ! request('status') ? 'active' : '' }}">All <span class="ml-1 text-xs text-ink-400">{{ $counts->sum() }}</span></a>
        @foreach (['Pending', 'Approved', 'Disbursed', 'Declined'] as $s)
            <a href="{{ route('credit.applications.index', ['status' => $s]) }}" class="tab {{ request('status') === $s ? 'active' : '' }}">{{ $s }} <span class="ml-1 text-xs text-ink-400">{{ $counts[$s] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="card">
        <x-filters :action="route('credit.applications.index')" placeholder="Application no., applicant, phone…">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Application</th><th>Date</th><th>Applicant</th><th>Purpose</th><th class="num">Requested</th><th>Term</th><th>Officer</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($applications as $a)
                    <tr>
                        <td><a href="{{ route('credit.applications.show', $a) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $a->application_no }}</a></td>
                        <td>{{ $a->application_date->format('d M Y') }}</td>
                        <td><span class="font-medium text-ink-900 dark:text-white">{{ $a->borrower->full_name }}</span><div class="text-xs text-ink-400">{{ $a->borrower->phone }}</div></td>
                        <td>{{ $a->purpose === 'Other' ? $a->purpose_other : $a->purpose }}</td>
                        <td class="num font-semibold">{{ Money::format($a->amount_requested) }}</td>
                        <td>{{ $a->preferred_term ? $a->preferred_term.' × '.strtolower($a->repayment_frequency) : '—' }}</td>
                        <td>{{ $a->officer?->name }}</td>
                        <td><x-badge :status="$a->status" /></td>
                        <td class="text-right"><a href="{{ route('credit.applications.show', $a) }}" class="btn btn-ghost btn-sm">Open <x-icon name="chevron-right" class="size-3.5" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty icon="file-text" title="No applications" text="New credit requests will appear here." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($applications->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $applications->links() }}</div>@endif
    </div>
</x-layouts.app>
