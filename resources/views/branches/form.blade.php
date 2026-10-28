@php $editing = $branch->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit branch' : 'Add branch'">
    <x-page-header :title="$editing ? 'Edit '.$branch->name : 'Add branch'" :back="route('branches.index')" />
    <form method="POST" action="{{ $editing ? route('branches.update', $branch) : route('branches.store') }}" class="card max-w-xl">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card-body space-y-4">
            <x-field label="Branch name" name="name" :value="$branch->name" required />
            <x-field label="Location" name="location" :value="$branch->location" />
            <x-field label="Phone" name="phone" :value="$branch->phone" />
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 p-4 dark:border-ink-800">
            <a href="{{ route('branches.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add branch' }}</button>
        </div>
    </form>
</x-layouts.app>
