@php
    use App\Support\Money;
    $a = $application; $b = $a->borrower;
    $company = \App\Models\Setting::get('company_name', 'MK DUA PA');
    $box = fn ($on) => $on ? '☑' : '☐';
    $line = fn ($v) => $v !== null && $v !== '' ? e($v) : '<span class="text-ink-300">________________</span>';
    $t = $a->approved_amount ? $a->approvedTotals() : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $a->application_no }} · Credit application</title>
    <x-favicons />
    @vite(['resources/css/app.css'])
    <style>@page { size: A4; margin: 12mm; }</style>
</head>
<body class="bg-ink-100 py-8 print:bg-white print:py-0">
<div class="no-print mx-auto mb-4 flex max-w-[210mm] justify-between px-2">
    <a href="{{ route('credit.applications.show', $a) }}" class="btn btn-secondary btn-sm">← Back</a>
    <button onclick="window.print()" class="btn btn-dark btn-sm">Print</button>
</div>
<div class="mx-auto max-w-[210mm] bg-white p-10 text-[12.5px] leading-relaxed text-ink-900 shadow-xl print:p-0 print:shadow-none">
    <div class="flex items-start justify-between border-b-2 border-ink-900 pb-4">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="" class="mb-2 h-16 w-auto">
            <h1 class="font-display text-2xl font-extrabold tracking-wide">{{ strtoupper($company) }}</h1>
            <p class="text-xs italic text-ink-500">{{ \App\Models\Setting::get('company_tagline', 'Small credit • Big dreams • Brighter futures') }}</p>
            <p class="mt-3">Application No.: <strong>{{ $a->application_no }}</strong></p>
            <p>Credit Officer: {!! $line($a->officer?->name) !!}</p>
        </div>
        <div class="text-right">
            <div class="ml-auto grid h-32 w-28 place-items-center overflow-hidden border border-ink-400 text-[10px] text-ink-400">
                @if ($b->photo_path)<img src="{{ asset('storage/'.$b->photo_path) }}" class="size-full object-cover" alt="">@else PASSPORT PHOTO @endif
            </div>
            <p class="mt-2">Date of Application: <strong>{{ $a->application_date->format('d / m / Y') }}</strong></p>
            <p>Branch / Location: {!! $line($a->branch?->name) !!}</p>
        </div>
    </div>

    <h2 class="mt-5 bg-ink-900 px-3 py-1 text-xs font-bold uppercase tracking-widest text-white">A · Personal information</h2>
    <div class="mt-3 grid grid-cols-2 gap-x-8 gap-y-1.5">
        <p>1. Full Name: {!! $line($b->displayName()) !!}</p>
        <p>8. Ghana Card No.: {!! $line($b->ghana_card) !!}</p>
        <p>2. Father's Name: {!! $line($b->father_name) !!}</p>
        <p>9. Residential Address: {!! $line($b->residential_address) !!}</p>
        <p>3. Mother's Name: {!! $line($b->mother_name) !!}</p>
        <p>10. Digital Address: {!! $line($b->digital_address) !!}</p>
        <p>4. Date of Birth: {!! $line($b->date_of_birth?->format('d / m / Y')) !!} Age: {!! $line($b->age()) !!}</p>
        <p>11. City/Town: {!! $line($b->city) !!} District: {!! $line($b->district) !!}</p>
        <p>5. Gender: {{ $box($b->gender === 'Male') }} Male {{ $box($b->gender === 'Female') }} Female</p>
        <p>12. Mobile No.: {!! $line($b->phone) !!}</p>
        <p>6. Marital Status: @foreach (['Single', 'Married', 'Divorced', 'Widowed'] as $m) {{ $box($b->marital_status === $m) }} {{ $m }} @endforeach</p>
        <p>13. Email Address: {!! $line($b->email) !!}</p>
        <p>7. Nationality: {!! $line($b->nationality) !!}</p>
    </div>

    <h2 class="mt-5 bg-ink-900 px-3 py-1 text-xs font-bold uppercase tracking-widest text-white">B · Employment / business information</h2>
    <div class="mt-3 grid grid-cols-2 gap-x-8 gap-y-1.5">
        <p>1. Occupation / Business: {!! $line($b->occupation) !!}</p>
        <p>6. Monthly Income (GH₵): {!! $line($b->monthly_income ? number_format($b->monthly_income, 2) : null) !!}</p>
        <p>2. Employer / Business Name: {!! $line($b->employer_name) !!}</p>
        <p>7. Business Location: {!! $line($b->business_location) !!}</p>
        <p>3. Nature: @foreach (['Trading', 'Agriculture', 'Service', 'Manufacturing', 'Other'] as $n) {{ $box($b->business_nature === $n) }} {{ $n }} @endforeach</p>
        <p>8. Business Address: {!! $line($b->business_address) !!}</p>
        <p>4. Business Registration No.: {!! $line($b->business_registration_no) !!}</p>
        <p>9. Contact Person: {!! $line($b->business_contact_person) !!}</p>
        <p>5. Years in Operation: {!! $line($b->years_in_operation) !!}</p>
        <p>10. Telephone: {!! $line($b->business_phone) !!}</p>
    </div>

    <h2 class="mt-5 bg-ink-900 px-3 py-1 text-xs font-bold uppercase tracking-widest text-white">C · Credit request</h2>
    <div class="mt-3 grid grid-cols-2 gap-x-8 gap-y-1.5">
        <p>1. Amount Requested (GH₵): <strong>{{ number_format($a->amount_requested, 2) }}</strong></p>
        <p>4. Repayment Frequency: @foreach (['Daily', 'Weekly', 'Bi-weekly', 'Monthly'] as $f) {{ $box($a->repayment_frequency === $f) }} {{ $f }} @endforeach</p>
        <p class="col-span-1">2. Purpose: @foreach (\App\Models\LoanApplication::PURPOSES as $p) {{ $box($a->purpose === $p) }} {{ $p }}@if($p === 'Other' && $a->purpose_other): {{ $a->purpose_other }}@endif&nbsp; @endforeach</p>
        <p>5. Preferred Disbursement Date: {!! $line($a->preferred_disbursement_date?->format('d / m / Y')) !!}<br>6. Proposed Repayment Start: {!! $line($a->proposed_repayment_start?->format('d / m / Y')) !!}</p>
        <p>3. Preferred Loan Term: {!! $line($a->preferred_term) !!}</p>
    </div>

    <h2 class="mt-5 bg-ink-900 px-3 py-1 text-xs font-bold uppercase tracking-widest text-white">D · Supporting information</h2>
    <p class="mt-3 font-semibold">1. Details of Other Family Members</p>
    <table class="mt-1 w-full border-collapse text-left">
        <tr class="border-b border-ink-400"><th class="py-1">Name</th><th>Age</th><th>Relationship</th><th>Occupation / Profession</th></tr>
        @foreach (array_pad($a->family_members ?? [], 3, []) as $f)
            <tr class="h-6 border-b border-ink-200"><td>{{ $f['name'] ?? '' }}</td><td>{{ $f['age'] ?? '' }}</td><td>{{ $f['relationship'] ?? '' }}</td><td>{{ $f['occupation'] ?? '' }}</td></tr>
        @endforeach
    </table>
    <p class="mt-3 font-semibold">2. Existing Credit(s) / Financial Obligations</p>
    <table class="mt-1 w-full border-collapse text-left">
        <tr class="border-b border-ink-400"><th class="py-1">Lender / Institution</th><th>Balance (GH₵)</th><th>Monthly Repayment (GH₵)</th></tr>
        @foreach (array_pad($a->existing_obligations ?? [], 2, []) as $o)
            <tr class="h-6 border-b border-ink-200"><td>{{ $o['lender'] ?? '' }}</td><td>{{ isset($o['balance']) ? number_format((float) $o['balance'], 2) : '' }}</td><td>{{ isset($o['monthly_repayment']) ? number_format((float) $o['monthly_repayment'], 2) : '' }}</td></tr>
        @endforeach
    </table>

    <h2 class="mt-5 bg-ink-900 px-3 py-1 text-xs font-bold uppercase tracking-widest text-white">E · Declaration</h2>
    <p class="mt-3">I declare that the information provided in this application is true and complete to the best of my knowledge. I authorise {{ $company }} to verify the information supplied and conduct appropriate credit and identity checks for assessing this application.</p>
    <div class="mt-4 grid grid-cols-2 gap-x-8 gap-y-3">
        <p>Applicant Name: <strong>{{ $b->displayName() }}</strong></p>
        <p>Date: {{ $a->application_date->format('d / m / Y') }}</p>
        <p>Signature / Thumbprint: ____________________</p>
        <p>Guarantor Name: {!! $line($a->guarantor_name) !!}</p>
        <p></p>
        <p>Guarantor Signature: ____________________</p>
    </div>

    <div class="mt-6 border-2 border-ink-900 p-4">
        <p class="text-xs font-bold uppercase tracking-widest">For official use only</p>
        <div class="mt-2 grid grid-cols-2 gap-x-8 gap-y-1.5">
            <p>Application Status: @foreach (['Approved', 'Pending', 'Declined'] as $s) {{ $box($a->status === $s || ($s === 'Approved' && $a->status === 'Disbursed')) }} {{ $s }} @endforeach</p>
            <p>Interest / Service Charge: {!! $line($t ? number_format($t['interest'], 2).' ('.rtrim(rtrim(number_format($a->interest_rate, 3), '0'), '.').'% / period)' : null) !!}</p>
            <p>Approved Amount (GH₵): {!! $line($a->approved_amount ? number_format($a->approved_amount, 2) : null) !!}</p>
            <p>Total Amount Payable (GH₵): {!! $line($t ? number_format($t['total'], 2) : null) !!}</p>
            <p>Credit Term: {!! $line($a->approved_term) !!}</p>
            <p>Approved By: {!! $line($a->approver?->name) !!}</p>
            <p>Repayment Frequency: {!! $line($a->approved_frequency) !!}</p>
            <p>Signature: __________ Date: {!! $line($a->decided_at?->format('d / m / Y')) !!}</p>
        </div>
    </div>
    <p class="mt-6 text-center text-xs font-bold tracking-[0.3em]">TOGETHER WE BUILD BETTER LIVES</p>
</div>
</body>
</html>
