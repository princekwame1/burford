@php $editing = $collector->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit collector' : 'Add collector'">
    <x-page-header :title="$editing ? 'Edit '.$collector->name : 'Add collector'" :back="route('susu.collectors.index')" />
    <form method="POST" action="{{ $editing ? route('susu.collectors.update', $collector) : route('susu.collectors.store') }}" class="card max-w-3xl">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-field label="Collector ID" name="code" :value="$collector->code" required />
            <x-field label="Name" name="name" :value="$collector->name" required />
            <x-field label="Phone" name="phone" :value="$collector->phone" />
            <x-field label="Territory" name="territory" :value="$collector->territory" />
            <x-select label="Branch" name="branch_id" :value="$collector->branch_id" :options="$branches->pluck('name', 'id')" placeholder="—" />
            <x-select label="Login account" name="user_id" :value="$collector->user_id" :options="$users->pluck('name', 'id')" placeholder="— None —" help="Link a user with the Field Collector role so they see only their own book." />
            <x-field label="Start date" name="start_date" type="date" :value="$collector->start_date" />
            <x-select label="Status" name="status" :value="$collector->status" :options="['Active', 'Inactive', 'Suspended']" />
            <x-field label="Daily target" name="daily_target" type="number" :value="$collector->daily_target" prefix="GH₵" />
            <x-field label="Cash limit" name="cash_limit" type="number" :value="$collector->cash_limit" prefix="GH₵" help="Max cash a collector may hold before depositing." />
            <x-field label="Supervisor" name="supervisor" :value="$collector->supervisor" />
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 p-4 dark:border-ink-800">
            <a href="{{ route('susu.collectors.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add collector' }}</button>
        </div>
    </form>
</x-layouts.app>
