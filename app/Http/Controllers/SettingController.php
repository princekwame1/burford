<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SmsNotifier;
use App\Services\TextTango;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit(SmsNotifier $sms)
    {
        return view('settings.edit', [
            'templates' => collect(SmsNotifier::TEMPLATES)->map(fn ($t, $key) => [
                'label' => $t[0], 'value' => $sms->template($key), 'enabled' => (bool) Setting::get('sms_on_'.$key, '1'),
            ]),
            'hasToken' => (bool) Setting::get('texttango_token', config('services.texttango.token')),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_tagline' => ['nullable', 'string', 'max:160'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'default_interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'loan_prefix' => ['required', 'string', 'max:10'],
            'reminder_days_before' => ['required', 'integer', 'min:0', 'max:14'],
            'texttango_base_url' => ['required', 'url'],
            'texttango_sender_id' => ['nullable', 'string', 'max:11'],
            'texttango_token' => ['nullable', 'string', 'max:500'],
            'texttango_topup_url' => ['nullable', 'url'],
            'sms_low_balance' => ['nullable', 'numeric', 'min:0'],
            'templates' => ['array'],
            'templates.*' => ['nullable', 'string', 'max:918'],
            'toggles' => ['array'],
        ]);

        $values = collect($data)->except(['templates', 'toggles', 'texttango_token'])->all();
        $values['texttango_topup_url'] = $data['texttango_topup_url'] ?? 'https://app.texttango.com/wallet';
        $values['sms_low_balance'] = $data['sms_low_balance'] ?? 20;
        $values['sms_enabled'] = $request->boolean('sms_enabled') ? '1' : '0';

        // Token is write-only: leaving the field blank keeps the stored one.
        if (filled($data['texttango_token'] ?? null)) {
            $values['texttango_token'] = $data['texttango_token'];
        }

        foreach (array_keys(SmsNotifier::TEMPLATES) as $key) {
            $values['sms_tpl_'.$key] = $data['templates'][$key] ?? SmsNotifier::TEMPLATES[$key][1];
            $values['sms_on_'.$key] = isset($data['toggles'][$key]) ? '1' : '0';
        }

        Setting::put($values);
        cache()->forget('texttango.wallet');
        cache()->forget('texttango.topups');

        return back()->with('success', 'Settings saved.');
    }

    public function testSms(Request $request)
    {
        $request->validate(['phone' => ['required', 'string']]);
        $client = new TextTango;
        if (! $client->isConfigured()) {
            return back()->with('error', 'Add your TextTango API token first.');
        }

        // Sent inline (not queued) so the result can be shown immediately.
        $result = $client->send($request->phone, 'Test message from '.Setting::get('company_name', config('app.name')).'. SMS notifications are working.');

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Test SMS sent to '.TextTango::normalizePhone($request->phone).'.' : 'TextTango error: '.$result['error']);
    }
}
