@props(['label' => null, 'name', 'options' => [], 'value' => null, 'placeholder' => null, 'required' => false, 'help' => null])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $selected = (string) old($key, $value);
    // Accept ['a','b'] (value = label) or ['value' => 'label'].
    $isList = array_is_list(is_array($options) ? $options : $options->all());
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $key }}" class="label">{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <select id="{{ $key }}" name="{{ $name }}" @if($required) required @endif {{ $attributes->except('class') }}
        style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238792a8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E&quot;);background-size:16px;background-position:right 12px center;background-repeat:no-repeat"
        class="input appearance-none pr-10 {{ $errors->has($key) ? 'input-error' : '' }}">
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            @php $v = $isList ? $optLabel : $optValue; @endphp
            <option value="{{ $v }}" @selected($selected === (string) $v)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @error($key) <p class="error">{{ $message }}</p> @enderror
    @if ($help) <p class="help">{{ $help }}</p> @endif
</div>
