<?php

namespace App\Http\Controllers;

use App\Jobs\SendSms;
use App\Models\Borrower;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\SmsMessage;
use App\Models\SusuCustomer;
use App\Services\SmsNotifier;
use App\Services\TextTango;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SmsController extends Controller
{
    public const GROUPS = [
        'susu_active' => 'All active susu customers',
        'borrowers_open' => 'Borrowers with open loans',
        'borrowers_overdue' => 'Borrowers in arrears',
        'borrowers_all' => 'All borrowers',
        'collectors' => 'All active collectors',
    ];

    public function index(Request $request, TextTango $client)
    {
        $messages = SmsMessage::with('sender')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('to', 'like', "%$s%")->orWhere('message', 'like', "%$s%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate(30)->withQueryString();

        $stats = SmsMessage::selectRaw('status, COUNT(*) n')->whereDate('created_at', '>=', today()->subDays(30))
            ->groupBy('status')->pluck('n', 'status');

        return view('sms.index', [
            'messages' => $messages,
            'stats' => $stats,
            'wallet' => $client->isConfigured() ? cache()->remember('texttango.wallet', 300, fn () => $client->wallet()) : null,
            'configured' => $client->isConfigured(),
        ]);
    }

    /** Re-read the TextTango balance now, e.g. right after topping up. */
    public function refreshWallet(TextTango $client)
    {
        cache()->forget('texttango.wallet');
        cache()->forget('texttango.topups');

        if (! $client->isConfigured()) {
            return back()->with('error', 'TextTango is not connected yet. An administrator can add the API token in Settings.');
        }

        $wallet = cache()->remember('texttango.wallet', 300, fn () => $client->wallet());

        return $wallet['ok']
            ? back()->with('success', 'SMS credit balance: '.$wallet['currency'].' '.number_format($wallet['balance'], 2).'.')
            : back()->with('error', 'Could not read the balance from TextTango: '.$wallet['error']);
    }

    public function compose(Request $request)
    {
        return view('sms.compose', [
            'groups' => collect(self::GROUPS)->map(fn ($label, $key) => ['label' => $label, 'count' => $this->recipients($key)->count()]),
            'to' => $request->to,
        ]);
    }

    public function send(Request $request, SmsNotifier $sms)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:single,group'],
            'to' => ['required_if:mode,single', 'nullable', 'string'],
            'group' => ['required_if:mode,group', 'nullable', 'in:'.implode(',', array_keys(self::GROUPS))],
            'message' => ['required', 'string', 'max:918'],
        ]);

        $recipients = $data['mode'] === 'single'
            ? collect(preg_split('/[\s,;]+/', $data['to'], -1, PREG_SPLIT_NO_EMPTY))->map(fn ($p) => ['phone' => $p, 'name' => ''])
            : $this->recipients($data['group']);

        $count = 0;
        foreach ($recipients->unique('phone') as $r) {
            $message = $sms->render($data['message'], ['name' => $r['name']]);
            if ($sms->send($r['phone'], $message, $data['mode'] === 'single' ? 'manual' : 'bulk', force: true)) {
                $count++;
            }
        }

        return redirect()->route('sms.index')->with('success', "$count message(s) queued for delivery via TextTango.");
    }

    public function resend(SmsMessage $sms)
    {
        $sms->update(['status' => 'queued', 'error' => null, 'provider_uuid' => null]);
        SendSms::dispatchAfterResponse($sms->id);

        return back()->with('success', 'Message re-queued.');
    }

    public function status(SmsMessage $sms, TextTango $client)
    {
        if (! $sms->provider_uuid) {
            return back()->with('error', 'This message has no TextTango reference yet.');
        }
        $status = $client->messageStatus($sms->provider_uuid);

        return back()->with($status ? 'success' : 'error', $status ? "TextTango reports: $status" : 'Could not fetch status from TextTango.');
    }

    /** @return Collection<int, array{phone: string, name: string}> */
    protected function recipients(string $group): Collection
    {
        $map = fn ($rows, $name = 'name') => $rows->filter(fn ($r) => filled($r->phone))
            ->map(fn ($r) => ['phone' => $r->phone, 'name' => $r->{$name}])->values();

        return match ($group) {
            'susu_active' => $map(SusuCustomer::where('status', 'Active')->get(['name', 'phone'])),
            'borrowers_all' => $map(Borrower::get(['full_name', 'phone']), 'full_name'),
            'borrowers_open' => $map(Borrower::whereHas('loans', fn ($q) => $q->open())->get(['full_name', 'phone']), 'full_name'),
            'borrowers_overdue' => $map(Loan::with('borrower')->open()->get()->filter(fn ($l) => $l->arrears() > 0.009)
                ->map->borrower->unique('id'), 'full_name'),
            'collectors' => $map(Collector::where('status', 'Active')->get(['name', 'phone'])),
            default => collect(),
        };
    }
}
