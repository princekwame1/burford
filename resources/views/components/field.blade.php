@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'prefix' => null])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
    $val = $type === 'password' ? null : old($key, $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $key }}" class="label">{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-ink-400">{{ $prefix }}</span>
        @endif
        @if ($type === 'textarea')
            <textarea id="{{ $key }}" name="{{ $name }}" rows="3" {{ $attributes->except('class') }} class="input {{ $hasError ? 'input-error' : '' }}">{{ $val }}</textarea>
        @else
            <input id="{{ $key }}" name="{{ $name }}" type="{{ $type }}" value="{{ $val }}" @if($required) required @endif
                @if($type === 'number') step="{{ $attributes->get('step', '0.01') }}" @endif
                {{ $attributes->except(['class', 'step']) }}
                class="input {{ $prefix ? 'pl-12' : '' }} {{ $hasError ? 'input-error' : '' }}">
        @endif
    </div>
    @error($key) <p class="error">{{ $message }}</p> @enderror
    @if ($help) <p class="help">{{ $help }}</p> @endif
</div>
