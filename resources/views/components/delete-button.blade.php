@props(['action', 'confirm' => 'This cannot be undone.', 'title' => 'Delete this record?', 'button' => 'Yes, delete', 'label' => null])
<form method="POST" action="{{ $action }}" class="inline"
    data-confirm="{{ $confirm }}" data-confirm-title="{{ $title }}" data-confirm-button="{{ $button }}" data-confirm-tone="danger">
    @csrf @method('DELETE')
    <button {{ $attributes->merge(['class' => 'btn btn-danger-soft btn-sm']) }} title="Delete">
        <x-icon name="trash" class="size-3.5" />@if($label) {{ $label }} @endif
    </button>
</form>
