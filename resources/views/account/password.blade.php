<x-layouts.app title="Change password">
    <x-page-header title="Change password" :subtitle="'Signed in as '.auth()->user()->email" />

    <form method="POST" action="{{ route('account.password.update') }}" class="card max-w-lg">
        @csrf @method('PUT')
        <div class="card-body space-y-4">
            <x-field label="Current password" name="current_password" type="password" required autocomplete="current-password" />
            <x-field label="New password" name="password" type="password" required autocomplete="new-password" help="At least 8 characters. A short sentence you'll remember works well." />
            <x-field label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 px-5 py-4 dark:border-ink-800">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary"><x-icon name="key" class="size-4" /> Change password</button>
        </div>
    </form>
</x-layouts.app>
