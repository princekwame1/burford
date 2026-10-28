@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-ink-500 hover:text-ink-900 dark:hover:text-white">
                <x-icon name="arrow-left" class="size-3.5" /> Back
            </a>
        @endif
        <h1 class="truncate font-display text-2xl font-bold tracking-tight text-ink-900 dark:text-white">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 no-print">{{ $slot }}</div>
    @endif
</div>
