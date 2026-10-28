<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\TextTango;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSms implements ShouldQueue
{
    use Queueable;

    /** Never retry automatically: a retried send can deliver and bill twice. */
    public int $tries = 1;

    public function __construct(public int $smsId) {}

    public function handle(TextTango $client): void
    {
        $sms = SmsMessage::find($this->smsId);
        if (! $sms || $sms->status === 'sent') {
            return;
        }

        $result = $client->send($sms->to, $sms->message);

        $sms->update([
            'status' => $result['ok'] ? 'sent' : 'failed',
            'provider_uuid' => $result['uuid'],
            'error' => $result['error'],
        ]);
    }
}
