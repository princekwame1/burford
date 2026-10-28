@php use App\Support\Money; $company = \App\Models\Setting::get('company_name', 'BURFORD Collection'); @endphp
<x-layouts.app title="Receipt {{ $c->receipt_no }}">
    <div class="no-print mb-6 flex items-center justify-between">
        <a href="{{ route('susu.collections.index') }}" class="inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-ink-900"><x-icon name="arrow-left" class="size-4" /> Back</a>
        <button onclick="window.print()" class="btn btn-dark"><x-icon name="printer" class="size-4" /> Print</button>
    </div>
    <div class="card mx-auto max-w-md p-8">
        <div class="text-center">
            {{-- White tile keeps the navy logo readable when the screen is in dark mode --}}
            <span class="mx-auto inline-block rounded-xl bg-white px-3 py-2"><img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-auto"></span>
            <h1 class="mt-3 font-display text-lg font-extrabold">{{ $company }}</h1>
            <p class="text-xs text-ink-500">{{ \App\Models\Setting::get('company_address') }} {{ \App\Models\Setting::get('company_phone') }}</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-ink-500">Susu contribution receipt</p>
        </div>
        <dl class="mt-6 space-y-2.5 border-y border-dashed border-ink-200 py-5 text-sm">
            @foreach ([['Receipt no.', $c->receipt_no], ['Date', $c->date->format('d M Y')], ['Customer', $c->customer->name.' ('.$c->customer->code.')'], ['Collector', $c->collector?->name], ['Method', $c->payment_method], ['Expected', Money::format($c->expected)], ['Status', $c->status]] as [$l, $v])
                <div class="flex justify-between gap-4"><dt class="text-ink-500">{{ $l }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
            @endforeach
        </dl>
        <div class="mt-5 flex items-baseline justify-between">
            <span class="text-sm text-ink-500">Amount received</span>
            <span class="font-display text-2xl font-extrabold">{{ Money::format($c->collected) }}</span>
        </div>
        <div class="mt-1 flex items-baseline justify-between text-sm">
            <span class="text-ink-500">Account balance</span>
            <span class="font-semibold">{{ Money::format($balance) }}</span>
        </div>
        <p class="mt-8 text-center text-xs text-ink-400">Thank you for saving with us.</p>
    </div>
</x-layouts.app>
