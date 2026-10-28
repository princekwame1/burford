@php use App\Support\Money; use App\Models\Loan; $company = \App\Models\Setting::get('company_name', 'MK DUA PA'); $period = Loan::periodLabel($loan->frequency); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement {{ $loan->loan_no }}</title>
    <x-favicons />
    @vite(['resources/css/app.css'])
    <style>@page { size: A4; margin: 12mm; }</style>
</head>
<body class="bg-ink-100 py-8 print:bg-white print:py-0">
<div class="no-print mx-auto mb-4 flex max-w-[210mm] justify-between px-2">
    <a href="{{ route('credit.loans.show', $loan) }}" class="btn btn-secondary btn-sm">← Back</a>
    <button onclick="window.print()" class="btn btn-dark btn-sm">Print</button>
</div>
<div class="mx-auto max-w-[210mm] bg-white p-10 text-sm text-ink-900 shadow-xl print:p-0 print:shadow-none">
    <div class="flex items-start justify-between border-b-2 border-ink-900 pb-4">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="" class="mb-2 h-16 w-auto">
            <h1 class="font-display text-2xl font-extrabold">{{ strtoupper($company) }}</h1>
            <p class="text-xs text-ink-500">{{ \App\Models\Setting::get('company_address') }} {{ \App\Models\Setting::get('company_phone') }}</p>
        </div>
        <div class="text-right"><p class="font-display text-lg font-bold">Loan statement</p><p class="text-xs text-ink-500">Generated {{ now()->format('d M Y H:i') }}</p></div>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-6">
        <dl class="space-y-1">
            <div class="flex gap-2"><dt class="w-32 text-ink-500">Borrower</dt><dd class="font-semibold">{{ $loan->borrower->displayName() }}</dd></div>
            <div class="flex gap-2"><dt class="w-32 text-ink-500">Phone</dt><dd>{{ $loan->borrower->phone }}</dd></div>
            <div class="flex gap-2"><dt class="w-32 text-ink-500">Ghana Card</dt><dd>{{ $loan->borrower->ghana_card ?: '—' }}</dd></div>
            <div class="flex gap-2"><dt class="w-32 text-ink-500">Branch</dt><dd>{{ $loan->branch?->name }}</dd></div>
        </dl>
        <dl class="space-y-1">
            <div class="flex gap-2"><dt class="w-36 text-ink-500">Loan ID</dt><dd class="font-semibold">{{ $loan->loan_no }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 text-ink-500">Disbursed</dt><dd>{{ $loan->disbursement_date->format('d M Y') }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 text-ink-500">Terms</dt><dd>{{ rtrim(rtrim(number_format($loan->interest_rate, 3), '0'), '.') }}% flat × {{ $loan->term }} {{ strtolower($period) }}s</dd></div>
            <div class="flex gap-2"><dt class="w-36 text-ink-500">Status</dt><dd>{{ $loan->status }}</dd></div>
        </dl>
    </div>
    <div class="mt-5 grid grid-cols-4 gap-3">
        @foreach ([['Principal', $loan->principal], ['Interest', $loan->interest_amount], ['Repaid', $loan->amount_repaid], ['Outstanding', $loan->outstanding()]] as [$l, $v])
            <div class="rounded-lg border border-ink-200 p-3"><p class="text-xs text-ink-500">{{ $l }}</p><p class="font-display text-base font-bold">{{ Money::format($v) }}</p></div>
        @endforeach
    </div>
    <h2 class="mt-6 font-display font-bold">Repayment schedule</h2>
    <table class="mt-2 w-full border-collapse text-left text-xs">
        <tr class="border-b-2 border-ink-900"><th class="py-1.5">#</th><th>Due date</th><th class="text-right">Installment</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th class="pl-3">Status</th></tr>
        @foreach ($schedule as $row)
            <tr class="border-b border-ink-200"><td class="py-1">{{ $row['period'] }}</td><td>{{ $row['due_date']->format('d M Y') }}</td><td class="text-right">{{ number_format($row['amount'], 2) }}</td><td class="text-right">{{ number_format($row['paid'], 2) }}</td><td class="text-right">{{ number_format($row['balance'], 2) }}</td><td class="pl-3">{{ $row['status'] }}</td></tr>
        @endforeach
    </table>
    <h2 class="mt-6 font-display font-bold">Payments received</h2>
    <table class="mt-2 w-full border-collapse text-left text-xs">
        <tr class="border-b-2 border-ink-900"><th class="py-1.5">Date</th><th>Receipt</th><th>Method</th><th>Reference</th><th class="text-right">Amount</th></tr>
        @forelse ($loan->repayments as $r)
            <tr class="border-b border-ink-200"><td class="py-1">{{ $r->date->format('d M Y') }}</td><td>{{ $r->receipt_no }}</td><td>{{ $r->payment_method }}</td><td>{{ $r->reference }}</td><td class="text-right">{{ number_format($r->amount, 2) }}</td></tr>
        @empty
            <tr><td colspan="5" class="py-2 text-ink-400">No payments recorded.</td></tr>
        @endforelse
    </table>
    <p class="mt-10 text-center text-xs font-bold tracking-[0.3em]">TOGETHER WE BUILD BETTER LIVES</p>
</div>
</body>
</html>
