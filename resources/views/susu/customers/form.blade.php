@php $editing = $customer->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit customer' : 'New customer'">
    <x-page-header :title="$editing ? 'Edit '.$customer->name : 'Register susu customer'" subtitle="Customer master record" :back="$editing ? route('susu.customers.show', $customer) : route('susu.customers.index')" />

    <form method="POST" action="{{ $editing ? route('susu.customers.update', $customer) : route('susu.customers.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card lg:col-span-2">
            <div class="card-header"><h3 class="card-title">Personal details</h3></div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field label="Customer ID" name="code" :value="$customer->code" required />
                <x-field label="Full name" name="name" :value="$customer->name" required />
                <x-field label="Phone (for SMS)" name="phone" :value="$customer->phone" placeholder="024 000 0000" />
                <x-field label="Ghana Card No." name="ghana_card" :value="$customer->ghana_card" placeholder="GHA-000000000-0" />
                <x-field label="Address / area" name="address" :value="$customer->address" class="sm:col-span-2" />
                <x-field label="Notes" name="notes" type="textarea" :value="$customer->notes" class="sm:col-span-2" />
            </div>
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Savings plan</h3></div>
                <div class="card-body space-y-4">
                    @unless (auth()->user()->role === 'collector')
                        <x-select label="Collector" name="collector_id" :value="$customer->collector_id" :options="$collectors->pluck('name', 'id')" placeholder="— Unassigned —" />
                    @endunless
                    <div class="grid grid-cols-2 gap-4">
                        <x-select label="Plan" name="plan" :value="$customer->plan" :options="['Daily', 'Weekly', 'Monthly']" />
                        <x-select label="Frequency" name="frequency" :value="$customer->frequency" :options="['Daily', 'Weekly', 'Monthly']" />
                    </div>
                    <x-field label="Contribution amount" name="contribution" type="number" :value="$customer->contribution" prefix="GH₵" required help="Expected per collection" />
                    <x-field label="Opening balance" name="opening_balance" type="number" :value="$customer->opening_balance ?? 0" prefix="GH₵" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-field label="Start date" name="start_date" type="date" :value="$customer->start_date" />
                        <x-select label="Status" name="status" :value="$customer->status" :options="['Active', 'Dormant', 'Closed']" />
                    </div>
                </div>
            </div>
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Save changes' : 'Register customer' }}</button>
        </div>
    </form>
</x-layouts.app>
