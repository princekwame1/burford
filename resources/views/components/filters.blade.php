@props(['action', 'placeholder' => 'Search…'])
<form method="GET" action="{{ $action }}" class="flex flex-col gap-2 border-b border-ink-100 p-4 sm:flex-row sm:flex-wrap sm:items-center dark:border-ink-800">
    <div class="relative min-w-0 flex-1 sm:min-w-[220px]">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="input input-sm pl-9">
    </div>
    {{ $slot }}
    <div class="flex gap-2">
        <button class="btn btn-dark btn-sm"><x-icon name="filter" class="size-3.5" /> Filter</button>
        @if (collect(request()->except('page'))->filter()->isNotEmpty())
            <a href="{{ $action }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
    </div>
</form>
