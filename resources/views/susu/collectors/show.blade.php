@php use App\Support\Money; @endphp
<x-layouts.app :title="$collector->name">
    <x-page-header :title="$collector->name" :subtitle="$collector->code.' · '.$collector->territory.' · Supervisor: '.($collector->supervisor ?: '—')" :back="route('susu.collectors.index')">
        <a href="{{ route('susu.collections.sheet', ['collector' => $collector->id]) }}" class="btn btn-secondary"><x-icon name="clipboard" class="size-4" /> Collection sheet</a>
        <a href="{{ route('susu.reconciliations.create', ['collector' => $collector->id]) }}" class="btn btn-secondary"><x-icon name="scale" class="size-4" /> Reconcile</a>
        <a href="{{ route('susu.collectors.edit', $collector) }}" class="btn btn-dark"><x-icon name="pencil" class="size-4" /> Edit</a>
        <x-delete-button :action="route('susu.collectors.destroy', $collector)" title="Remove this collector?" confirm="Their customers will become unassigned." button="Yes, remove" class="btn btn-danger-soft" />
    </x-page-header>
    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Customers ({{ $collector->customers->count() }})</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Plan</th><th class="num">Contribution</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($collector->customers as $c)
                        <tr>
                            <td><a href="{{ route('susu.customers.show', $c) }}" class="font-medium text-ink-900 hover:text-brand-700 dark:text-white">{{ $c->name }}</a><div class="text-xs text-ink-400">{{ $c->code }}</div></td>
                            <td>{{ $c->plan }}</td>
                            <td class="num">{{ Money::format($c->contribution) }}</td>
                            <td><x-badge :status="$c->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty title="No customers assigned" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent receipts</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Customer</th><th class="num">Collected</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($recent as $r)
                            <tr><td>{{ $r->date->format('d M') }}</td><td>{{ $r->customer?->name }}</td><td class="num">{{ Money::format($r->collected) }}</td><td><x-badge :status="$r->status" /></td></tr>
                        @empty
                            <tr><td colspan="4"><x-empty title="No receipts yet" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Reconciliations</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th class="num">System</th><th class="num">Counted</th><th class="num">Variance</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($reconciliations as $r)
                            <tr><td>{{ $r->date->format('d M') }}</td><td class="num">{{ Money::format($r->system_collections, false) }}</td><td class="num">{{ Money::format($r->total_counted, false) }}</td>
                                <td class="num font-semibold {{ abs($r->variance) > 0.009 ? 'text-rose-600' : 'text-emerald-600' }}">{{ Money::format($r->variance, false) }}</td><td><x-badge :status="$r->status" /></td></tr>
                        @empty
                            <tr><td colspan="5"><x-empty title="No reconciliations yet" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
