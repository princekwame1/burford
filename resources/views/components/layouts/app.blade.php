@props(['title' => null])
@php
    $company = \App\Models\Setting::get('company_name', 'BURFORD Collection');
    $user = auth()->user();
    $is = fn (...$p) => collect($p)->contains(fn ($x) => request()->routeIs($x));
    $nav = [
        ['section' => 'Overview'],
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'active' => ['dashboard']],
        ['label' => 'Help & guide', 'icon' => 'sparkles', 'route' => 'help', 'active' => ['help']],
        ['section' => 'Susu Collections', 'roles' => ['manager', 'collector']],
        ['label' => 'Collection Sheet', 'icon' => 'clipboard', 'route' => 'susu.collections.sheet', 'active' => ['susu.collections.sheet'], 'roles' => ['manager', 'collector']],
        ['label' => 'Receipts', 'icon' => 'receipt', 'route' => 'susu.collections.index', 'active' => ['susu.collections.index', 'susu.collections.create', 'susu.collections.edit'], 'roles' => ['manager', 'collector']],
        ['label' => 'Customers', 'icon' => 'users', 'route' => 'susu.customers.index', 'active' => ['susu.customers.*'], 'roles' => ['manager', 'collector']],
        ['label' => 'Withdrawals', 'icon' => 'arrow-up-circle', 'route' => 'susu.withdrawals.index', 'active' => ['susu.withdrawals.*'], 'roles' => ['manager', 'collector']],
        ['label' => 'Collectors', 'icon' => 'id-card', 'route' => 'susu.collectors.index', 'active' => ['susu.collectors.*'], 'roles' => ['manager']],
        ['label' => 'Reconciliation', 'icon' => 'scale', 'route' => 'susu.reconciliations.index', 'active' => ['susu.reconciliations.*'], 'roles' => ['manager']],
        ['section' => 'Micro Credit', 'roles' => ['manager', 'loan_officer']],
        ['label' => 'Applications', 'icon' => 'file-text', 'route' => 'credit.applications.index', 'active' => ['credit.applications.*'], 'roles' => ['manager', 'loan_officer']],
        ['label' => 'Loans', 'icon' => 'landmark', 'route' => 'credit.loans.index', 'active' => ['credit.loans.*'], 'roles' => ['manager', 'loan_officer']],
        ['label' => 'Repayments', 'icon' => 'arrow-down-circle', 'route' => 'credit.repayments.index', 'active' => ['credit.repayments.*'], 'roles' => ['manager', 'loan_officer']],
        ['label' => 'Borrowers', 'icon' => 'user', 'route' => 'credit.borrowers.index', 'active' => ['credit.borrowers.*'], 'roles' => ['manager', 'loan_officer']],
        ['section' => 'Insights', 'roles' => ['manager']],
        ['label' => 'Reports', 'icon' => 'chart', 'route' => 'reports.index', 'active' => ['reports.*'], 'roles' => ['manager']],
        ['label' => 'SMS Centre', 'icon' => 'message', 'route' => 'sms.index', 'active' => ['sms.*'], 'roles' => ['manager']],
        ['section' => 'Administration', 'roles' => ['admin']],
        ['label' => 'Users', 'icon' => 'shield', 'route' => 'users.index', 'active' => ['users.*'], 'roles' => ['admin']],
        ['label' => 'Branches', 'icon' => 'building', 'route' => 'branches.index', 'active' => ['branches.*'], 'roles' => ['admin']],
        ['label' => 'Settings', 'icon' => 'settings', 'route' => 'settings.edit', 'active' => ['settings.*'], 'roles' => ['admin']],
    ];
    $can = fn ($item) => ! isset($item['roles']) || $user->isAdmin() || $user->hasRole(...$item['roles']);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ $company }}</title>
    <x-favicons />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        try { if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen" x-data="{ nav: false }">
    {{-- Sidebar --}}
    <aside class="no-print fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col bg-brand-950 transition-transform duration-200 lg:translate-x-0"
        :class="nav ? 'translate-x-0' : '-translate-x-full'">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_60%_at_0%_0%,rgba(217,150,1,.14),transparent_60%)]"></div>
        <a href="{{ route('dashboard') }}" class="relative flex items-center gap-3 px-5 pb-4 pt-6">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white p-1 shadow-lg shadow-black/30"><img src="{{ asset('images/logo-mark.png') }}" alt="" class="size-full object-contain"></span>
            <span class="leading-tight">
                <span class="block font-display text-[15px] font-extrabold tracking-wide text-white">{{ \Illuminate\Support\Str::of($company)->before(' ') }}</span>
                <span class="block text-[11px] font-medium uppercase tracking-[0.2em] text-gold-400">{{ \Illuminate\Support\Str::of($company)->after(' ') ?: 'Finance' }}</span>
            </span>
        </a>
        <nav class="sidebar-scroll relative flex-1 space-y-0.5 overflow-y-auto px-3 pb-6">
            @foreach ($nav as $item)
                @continue(! $can($item))
                @if (isset($item['section']))
                    <p class="nav-section">{{ $item['section'] }}</p>
                @else
                    <a href="{{ route($item['route']) }}" class="nav-link {{ $is(...$item['active']) ? 'active' : '' }}">
                        <x-icon :name="$item['icon']" class="size-[18px] text-ink-400" /> {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
        <div class="relative border-t border-white/5 p-3">
            <div class="flex items-center gap-1 rounded-xl px-1 py-1">
                <a href="{{ route('account.password') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg px-1 py-1 hover:bg-white/5" title="Change password">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white/10 text-xs font-bold text-white">{{ $user->initials() }}</span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-[13px] font-semibold text-white">{{ $user->name }}</span>
                        <span class="block truncate text-[11px] text-ink-400">{{ $user->roleLabel() }}</span>
                    </span>
                </a>
                <a href="{{ route('account.password') }}" class="rounded-lg p-2 text-ink-400 hover:bg-white/5 hover:text-white {{ request()->routeIs('account.*') ? 'text-gold-400' : '' }}" title="Change password"><x-icon name="key" class="size-4" /></a>
                <form method="POST" action="{{ route('logout') }}" data-confirm="You will need to log in again to continue." data-confirm-title="Sign out?" data-confirm-button="Sign out">@csrf
                    <button class="rounded-lg p-2 text-ink-400 hover:bg-white/5 hover:text-white" title="Sign out"><x-icon name="logout" class="size-4" /></button>
                </form>
            </div>
        </div>
    </aside>
    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-ink-950/50 backdrop-blur-sm lg:hidden"></div>

    <div class="lg:pl-[264px]">
        {{-- Top bar --}}
        <header class="no-print sticky top-0 z-20 border-b border-ink-200/60 bg-ink-50/80 backdrop-blur-xl dark:border-ink-800 dark:bg-ink-950/80">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button @click="nav = true" class="btn btn-ghost -ml-2 p-2 lg:hidden" aria-label="Open menu"><x-icon name="menu" /></button>
                <div class="hidden text-sm text-ink-500 sm:block">{{ now()->format('l, j F Y') }}</div>
                <div class="ml-auto flex items-center gap-1.5">
                    @if ($user->hasRole('admin', 'manager', 'collector'))
                        <a href="{{ route('susu.collections.sheet') }}" class="btn btn-secondary btn-sm hidden sm:inline-flex"><x-icon name="plus" class="size-3.5" /> Collection</a>
                    @endif
                    @if ($user->hasRole('admin', 'manager', 'loan_officer'))
                        <a href="{{ route('credit.applications.create') }}" class="btn btn-primary btn-sm hidden sm:inline-flex"><x-icon name="plus" class="size-3.5" /> Loan application</a>
                    @endif
                    <button onclick="toggleTheme()" class="btn btn-ghost p-2" title="Toggle dark mode">
                        <x-icon name="moon" class="size-[18px] dark:hidden" /><x-icon name="sun" class="hidden size-[18px] dark:block" />
                    </button>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    {{-- Flash messages, shown as SweetAlert2 toasts / dialogs by resources/js/alerts.js --}}
    <script>
        window.__flash = @js([
            'success' => session('success'),
            'error' => session('error'),
            'validation' => $errors->any() ? 'Please correct the '.\Illuminate\Support\Str::plural('field', $errors->count()).' marked in red.' : null,
        ]);
    </script>
    @stack('scripts')
</body>
</html>
