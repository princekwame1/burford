@php use App\Models\Setting; @endphp
<x-layouts.app title="Settings">
    <x-page-header title="Settings" subtitle="Company profile, lending defaults and SMS notifications." />
    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-6 xl:col-span-2">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Company</h3></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <x-field label="Company name" name="company_name" :value="Setting::get('company_name', 'MK DUA PA')" required />
                    <x-field label="Tagline" name="company_tagline" :value="Setting::get('company_tagline')" />
                    <x-field label="Phone" name="company_phone" :value="Setting::get('company_phone')" />
                    <x-field label="Address" name="company_address" :value="Setting::get('company_address')" />
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Lending defaults</h3></div>
                <div class="card-body grid gap-4 sm:grid-cols-3">
                    <x-field label="Default interest % per period" name="default_interest_rate" type="number" step="0.001" :value="Setting::get('default_interest_rate', 5)" required />
                    <x-field label="Loan ID prefix" name="loan_prefix" :value="Setting::get('loan_prefix', 'BCL-')" required />
                    <x-field label="Reminder days before due" name="reminder_days_before" type="number" step="1" :value="Setting::get('reminder_days_before', 1)" required help="Daily reminder job at 08:00" />
                </div>
            </div>
            <div class="card" id="sms">
                <div class="card-header">
                    <div><h3 class="card-title">TextTango SMS</h3><p class="text-xs text-ink-500">Create a token in TextTango → Profile → API Tokens.</p></div>
                    <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="sms_enabled" value="1" class="checkbox" @checked(Setting::get('sms_enabled', '1'))> Notifications on</label>
                </div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <x-field label="API token" name="texttango_token" type="password" :placeholder="$hasToken ? '•••••••• saved — leave blank to keep' : 'Paste your TextTango API token'" class="sm:col-span-2" autocomplete="off" />
                    <x-field label="Sender ID" name="texttango_sender_id" :value="Setting::get('texttango_sender_id', config('services.texttango.sender_id'))" help="Max 11 characters; must be approved on your TextTango account." />
                    <x-field label="API base URL" name="texttango_base_url" :value="Setting::get('texttango_base_url', config('services.texttango.base_url'))" required />
                    <x-field label="Top-up page" name="texttango_topup_url" :value="\App\Services\TextTango::topUpUrl()" help="Where the dashboard's Top up button sends you to pay for credit." />
                    <x-field label="Warn when credit falls below" name="sms_low_balance" type="number" :value="Setting::get('sms_low_balance', 20)" prefix="GH₵" help="The dashboard turns amber below this amount." />
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div><h3 class="card-title">Notification templates</h3><p class="text-xs text-ink-500">Placeholders: {name} {amount} {balance} {date} {reference} {receipt} {total} {term} {installment} {period} {first_due} {days} {company}</p></div></div>
                <div class="divide-y divide-ink-100 dark:divide-ink-800">
                    @foreach ($templates as $key => $t)
                        <div class="grid gap-3 p-5 sm:grid-cols-[220px_1fr]">
                            <label class="flex items-start gap-2.5">
                                <input type="checkbox" name="toggles[{{ $key }}]" value="1" class="checkbox mt-0.5" @checked($t['enabled'])>
                                <span><span class="block text-sm font-semibold text-ink-900 dark:text-white">{{ $t['label'] }}</span><span class="text-xs text-ink-500">{{ $key }}</span></span>
                            </label>
                            <textarea name="templates[{{ $key }}]" rows="2" class="input text-[13px]">{{ $t['value'] }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end"><button class="btn btn-primary px-6">Save settings</button></div>
        </form>

        <div class="space-y-6">
            <form method="POST" action="{{ route('settings.sms-test') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Send a test SMS</h3></div>
                <div class="card-body space-y-3">
                    <x-field label="Phone number" name="phone" placeholder="024 000 0000" required />
                    <button class="btn btn-dark w-full"><x-icon name="send" class="size-4" /> Send test</button>
                    <p class="text-xs text-ink-500">Sent immediately using the saved token, so you see TextTango's answer right away.</p>
                </div>
            </form>
            <div class="card card-body text-sm">
                <p class="font-semibold text-ink-900 dark:text-white">Automated messages</p>
                <ul class="mt-3 space-y-2 text-ink-600 dark:text-ink-300">
                    <li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" /> Receipt on every susu collection & withdrawal</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" /> Approval / decline of loan applications</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" /> Disbursement with repayment plan</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" /> Receipt for each loan repayment</li>
                    <li class="flex gap-2"><x-icon name="clock" class="size-4 shrink-0 text-brand-600" /> Daily reminders before due dates & overdue notices (day 1, 3, 7, then weekly)</li>
                </ul>
                <p class="mt-4 rounded-xl bg-ink-50 p-3 text-xs text-ink-500 dark:bg-ink-800">Scheduled jobs need the Laravel scheduler running: <code>php artisan schedule:work</code> (or a cron entry for <code>schedule:run</code>).</p>
            </div>
        </div>
    </div>
</x-layouts.app>
