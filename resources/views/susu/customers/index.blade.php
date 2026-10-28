@php use App\Support\Money; @endphp
<x-layouts.app title="Susu customers">
    <x-page-header title="Susu customers" subtitle="Customer master — every saver, their plan and current balance.">
        @if (auth()->user()->hasRole('admin', 'manager'))
            <a href="{{ route('reports.export', 'customers') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Export</a>
        @endif
        <a href="{{ route('susu.customers.create') }}" class="btn btn-primary"><x-icon name="user-plus" class="size-4" /> New customer</a>
    </x-page-header>

    <div class="card">
        <x-filters :action="route('susu.customers.index')" placeholder="Search name, ID or phone…">
            @unless (auth()->user()->role === 'collector')
                <select name="collector" class="input input-sm sm:w-44">
                    <option value="">All collectors</option>
                    @foreach ($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            @endunless
            <select name="status" class="input input-sm sm:w-36">
                <option value="">Any status</option>
                @foreach (['Active', 'Dormant', 'Closed'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Customer</th><th>Phone</th><th>Collector</th><th>Plan</th><th class="num">Contribution</th><th class="num">Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($customers as $c)
                    @php $bal = (float) $c->opening_balance + (float) $c->total_collected - (float) $c->total_withdrawn - (float) $c->total_commission; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('susu.customers.show', $c) }}" class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ collect(explode(' ', $c->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                                <span><span class="block font-medium text-ink-900 dark:text-white">{{ $c->name }}</span><span class="text-xs text-ink-400">{{ $c->code }} · {{ $c->address }}</span></span>
                            </a>
                        </td>
                        <td>{{ $c->phone }}</td>
                        <td>{{ $c->collector?->name ?? '—' }}</td>
                        <td>{{ $c->plan }}</td>
                        <td class="num">{{ Money::format($c->contribution) }}</td>
                        <td class="num font-semibold text-ink-900 dark:text-white">{{ Money::format($bal) }}</td>
                        <td><x-badge :status="$c->status" /></td>
                        <td class="text-right">
                            <a href="{{ route('susu.collections.create', ['customer' => $c->id]) }}" class="btn btn-ghost btn-sm" title="Record collection"><x-icon name="plus" class="size-3.5" /></a>
                            <a href="{{ route('susu.customers.edit', $c) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-3.5" /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty icon="users" title="No customers found" text="Register your first susu customer to start collecting."><a href="{{ route('susu.customers.create') }}" class="btn btn-primary btn-sm">New customer</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $customers->links() }}</div>@endif
    </div>
</x-layouts.app>
