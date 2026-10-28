<x-layouts.app title="Users">
    <x-page-header title="Users & roles" subtitle="Who can sign in and what they can see.">
        <a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="user-plus" class="size-4" /> Add user</a>
    </x-page-header>
    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Administrator', 'Everything, including users, branches and settings.'], ['Manager', 'All operations, reports, SMS centre, approvals and disbursement.'], ['Loan Officer', 'Borrowers, applications, loans and repayments.'], ['Field Collector', 'Their own susu customers, collection sheet, receipts and withdrawals.']] as [$r, $d])
            <div class="rounded-2xl border border-ink-200/70 bg-white/60 p-4 text-sm dark:border-ink-800 dark:bg-ink-900/60"><p class="font-semibold text-ink-900 dark:text-white">{{ $r }}</p><p class="mt-1 text-xs text-ink-500">{{ $d }}</p></div>
        @endforeach
    </div>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-ink-900 text-xs font-bold text-white dark:bg-white dark:text-ink-900">{{ $u->initials() }}</span><span class="font-medium text-ink-900 dark:text-white">{{ $u->name }}</span></div></td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->phone }}</td>
                        <td><x-badge :tone="['admin' => 'violet', 'manager' => 'blue', 'loan_officer' => 'green', 'collector' => 'amber'][$u->role] ?? 'ink'">{{ $u->roleLabel() }}</x-badge>
                            @if ($u->collector)<span class="ml-1 text-xs text-ink-400">{{ $u->collector->code }}</span>@endif</td>
                        <td><x-badge :status="$u->is_active ? 'Active' : 'Inactive'" /></td>
                        <td class="text-right">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-3.5" /></a>
                            @unless ($u->is(auth()->user()))<x-delete-button :action="route('users.destroy', $u)" :title="'Delete '.$u->name.'?'" confirm="They will no longer be able to sign in." />@endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
