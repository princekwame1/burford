@php use App\Support\Money; @endphp
<x-layouts.app title="Borrowers">
    <x-page-header title="Borrowers" subtitle="Everyone who has applied for or holds a loan.">
        <a href="{{ route('credit.borrowers.create') }}" class="btn btn-primary"><x-icon name="user-plus" class="size-4" /> New borrower</a>
    </x-page-header>
    <div class="card">
        <x-filters :action="route('credit.borrowers.index')" placeholder="Name, ID, phone or Ghana Card…">
            <select name="branch" class="input input-sm sm:w-44">
                <option value="">All branches</option>
                @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Borrower</th><th>Phone</th><th>Ghana Card</th><th>Occupation</th><th>Branch</th><th class="num">Loans</th><th class="num">Total borrowed</th><th></th></tr></thead>
                <tbody>
                @forelse ($borrowers as $b)
                    <tr>
                        <td>
                            <a href="{{ route('credit.borrowers.show', $b) }}" class="flex items-center gap-3">
                                @if ($b->photo_path)
                                    <img src="{{ asset('storage/'.$b->photo_path) }}" class="size-9 rounded-full object-cover" alt="">
                                @else
                                    <span class="grid size-9 place-items-center rounded-full bg-sky-50 text-xs font-bold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">{{ mb_substr($b->full_name, 0, 1) }}</span>
                                @endif
                                <span><span class="block font-medium text-ink-900 dark:text-white">{{ $b->displayName() }}</span><span class="text-xs text-ink-400">{{ $b->code }}</span></span>
                            </a>
                        </td>
                        <td>{{ $b->phone }}</td>
                        <td class="text-xs">{{ $b->ghana_card ?: '—' }}</td>
                        <td>{{ $b->occupation ?: '—' }}</td>
                        <td>{{ $b->branch?->name ?? '—' }}</td>
                        <td class="num">{{ $b->loans_count }}</td>
                        <td class="num font-semibold">{{ Money::format($b->total_borrowed) }}</td>
                        <td class="text-right">
                            <a href="{{ route('credit.applications.create', ['borrower' => $b->id]) }}" class="btn btn-ghost btn-sm" title="New application"><x-icon name="file-text" class="size-3.5" /></a>
                            <a href="{{ route('credit.borrowers.edit', $b) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-3.5" /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty icon="user" title="No borrowers found" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($borrowers->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $borrowers->links() }}</div>@endif
    </div>
</x-layouts.app>
