@php $editing = $user->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit user' : 'Add user'">
    <x-page-header :title="$editing ? 'Edit '.$user->name : 'Add user'" :back="route('users.index')" />
    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="card max-w-2xl">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-field label="Full name" name="name" :value="$user->name" required />
            <x-field label="Email" name="email" type="email" :value="$user->email" required />
            <x-field label="Phone" name="phone" :value="$user->phone" />
            <x-select label="Role" name="role" :value="$user->role" :options="\App\Models\User::ROLES" required />
            <x-field label="{{ $editing ? 'New password' : 'Password' }}" name="password" type="password" :required="! $editing" :help="$editing ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" autocomplete="new-password" />
            <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" />
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $user->is_active))> Account active (can sign in)</label>
            <p class="text-xs text-ink-500 sm:col-span-2">For field collectors, link this account to a collector record under Susu → Collectors so they only see their own customers.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 p-4 dark:border-ink-800">
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create user' }}</button>
        </div>
    </form>
</x-layouts.app>
