{{-- Open with $dispatch('open-modal', 'name') (or window.dispatchEvent); close with Esc, the backdrop, or $dispatch('close-modal'). --}}
@props(['name', 'title' => null, 'subtitle' => null, 'show' => false, 'width' => 'max-w-lg'])
<div x-data="{ open: @js((bool) $show) }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:close-modal.window="if (! $event.detail || $event.detail === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open); if (open) $nextTick(() => $refs.panel.querySelector('input:not([type=hidden]), select, textarea, button')?.focus())"
    x-show="open" x-cloak
    class="no-print fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ink-950/50 backdrop-blur-sm" x-on:click="open = false"></div>
    <div class="relative flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-ref="panel" x-show="open"
            x-transition:enter="duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            {{ $attributes->merge(['class' => "card relative w-full $width shadow-2xl shadow-ink-900/20"]) }}>
            @if ($title)
                <div class="card-header">
                    <div><h3 class="card-title">{{ $title }}</h3>@if($subtitle)<p class="text-xs text-ink-500">{{ $subtitle }}</p>@endif</div>
                    <button type="button" x-on:click="open = false" class="btn btn-ghost -mr-2 p-1.5" aria-label="Close"><x-icon name="x" class="size-4" /></button>
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
