@props(['icon' => 'sparkles', 'title' => 'Nothing here yet', 'text' => null])
<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <span class="grid size-12 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><x-icon :name="$icon" /></span>
    <p class="mt-4 font-display font-semibold text-ink-900 dark:text-white">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-ink-500">{{ $text }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
