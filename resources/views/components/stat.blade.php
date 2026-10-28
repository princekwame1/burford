@props(['label', 'value', 'icon' => 'chart', 'tone' => 'brand', 'sub' => null, 'href' => null])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20',
        'ink' => 'bg-ink-100 text-ink-700 ring-ink-200 dark:bg-ink-800 dark:text-ink-200 dark:ring-ink-700',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card group relative min-w-0 overflow-hidden p-4 sm:p-5 transition '.($href ? 'hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-900/5' : '')]) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-[12.5px] font-medium text-ink-500 dark:text-ink-400">{{ $label }}</p>
        <span class="grid size-8 shrink-0 place-items-center rounded-lg ring-1 {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="size-4" />
        </span>
    </div>
    <p class="mt-2 break-words font-display text-[clamp(1.15rem,1.6vw,1.5rem)] font-bold leading-tight tracking-tight text-ink-900 tabular-nums dark:text-white">{{ $value }}</p>
    @if ($sub || $slot->isNotEmpty())
        <div class="mt-3 text-xs text-ink-500 dark:text-ink-400">{{ $sub }}{{ $slot }}</div>
    @endif
</{{ $tag }}>
