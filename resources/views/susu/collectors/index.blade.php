@php use App\Support\Money; @endphp
<x-layouts.app title="Collectors">
    <x-page-header title="Collector register" subtitle="Field agents, their territory, targets and today's takings.">
        <a href="{{ route('susu.collectors.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add collector</a>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($collectors as $c)
            @php $progress = $c->daily_target > 0 ? min($c->today_collected / $c->daily_target * 100, 100) : 0; @endphp
            <a href="{{ route('susu.collectors.show', $c) }}" class="card group p-5 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-900/5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-ink-900 font-display text-sm font-bold text-white dark:bg-white dark:text-ink-900">{{ substr($c->code, -2) }}</span>
                        <div>
                            <p class="font-display font-bold text-ink-900 dark:text-white">{{ $c->name }}</p>
                            <p class="text-xs text-ink-500">{{ $c->code }} · {{ $c->territory }}</p>
                        </div>
                    </div>
                    <x-badge :status="$c->status" />
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-ink-50 p-2.5 dark:bg-ink-800/60"><p class="text-[11px] text-ink-500">Customers</p><p class="font-display font-bold text-ink-900 dark:text-white">{{ $c->customers_count }}</p></div>
                    <div class="rounded-xl bg-ink-50 p-2.5 dark:bg-ink-800/60"><p class="text-[11px] text-ink-500">Today</p><p class="font-display font-bold text-ink-900 tabular-nums dark:text-white">{{ number_format($c->today_collected ?? 0) }}</p></div>
                    <div class="rounded-xl bg-ink-50 p-2.5 dark:bg-ink-800/60"><p class="text-[11px] text-ink-500">This month</p><p class="font-display font-bold text-ink-900 tabular-nums dark:text-white">{{ number_format($c->month_collected ?? 0) }}</p></div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-ink-500"><span>Daily target {{ Money::format($c->daily_target) }}</span><span>{{ number_format($progress) }}%</span></div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $progress }}%"></div></div>
                </div>
                <p class="mt-4 flex items-center gap-1.5 text-xs text-ink-500"><x-icon name="phone" class="size-3.5" /> {{ $c->phone ?: '—' }} <span class="mx-1">·</span> Cash limit {{ Money::format($c->cash_limit) }}</p>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty icon="id-card" title="No collectors yet"><a href="{{ route('susu.collectors.create') }}" class="btn btn-primary btn-sm">Add collector</a></x-empty></div>
        @endforelse
    </div>
</x-layouts.app>
