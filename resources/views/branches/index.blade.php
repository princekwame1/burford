<x-layouts.app title="Branches">
    <x-page-header title="Branches" subtitle="Locations used for loans, borrowers and collectors.">
        <a href="{{ route('branches.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add branch</a>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($branches as $b)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300"><x-icon name="building" class="size-5" /></span>
                        <div><p class="font-display font-bold text-ink-900 dark:text-white">{{ $b->name }}</p><p class="text-xs text-ink-500">{{ $b->location }}</p></div>
                    </div>
                    <div class="flex">
                        <a href="{{ route('branches.edit', $b) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-3.5" /></a>
                        <x-delete-button :action="route('branches.destroy', $b)" :title="'Delete '.$b->name.'?'" />
                    </div>
                </div>
                <div class="mt-4 flex gap-4 text-sm text-ink-500"><span><strong class="text-ink-900 dark:text-white">{{ $b->loans_count }}</strong> loans</span><span><strong class="text-ink-900 dark:text-white">{{ $b->collectors_count }}</strong> collectors</span>@if($b->phone)<span>{{ $b->phone }}</span>@endif</div>
            </div>
        @empty
            <div class="card md:col-span-3"><x-empty icon="building" title="No branches yet" /></div>
        @endforelse
    </div>
</x-layouts.app>
