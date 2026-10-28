<?php

namespace App\Services;

use App\Jobs\SendSms;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\SusuCollection;
use App\Models\SusuWithdrawal;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns business events into SMS messages using editable templates (Settings → SMS).
 */
class SmsNotifier
{
    /** Event key => [label, default template]. Placeholders are wrapped in {braces}. */
    public const TEMPLATES = [
        'collection' => ['Susu contribution received',
            'Dear {name}, we received {amount} for your susu account on {date}. Receipt {receipt}. Balance: {balance}. Thank you - {company}'],
        'withdrawal' => ['Susu withdrawal paid',
            'Dear {name}, a withdrawal of {amount} was paid from your susu account on {date}. Ref {reference}. Balance: {balance}. {company}'],
        'loan_approved' => ['Loan application approved',
            'Dear {name}, your loan application {reference} has been APPROVED for {amount}. Total payable {total} over {term}. Visit us for disbursement. {company}'],
        'loan_declined' => ['Loan application declined',
            'Dear {name}, we are sorry, your loan application {reference} was not approved at this time. Please contact us for details. {company}'],
        'loan_disbursed' => ['Loan disbursed',
            'Dear {name}, loan {reference} of {amount} has been disbursed. Pay {installment} per {period} from {first_due}. Total payable {total}. {company}'],
        'repayment' => ['Loan repayment received',
            'Dear {name}, we received {amount} for loan {reference} on {date}. Outstanding balance: {balance}. Thank you - {company}'],
        'reminder' => ['Repayment reminder (before due date)',
            'Dear {name}, reminder: your installment of {amount} for loan {reference} is due on {date}. Outstanding: {balance}. {company}'],
        'overdue' => ['Overdue / arrears notice',
            'Dear {name}, your loan {reference} is {days} day(s) overdue with arrears of {amount}. Please pay promptly to avoid penalties. {company}'],
    ];

    public function __construct(protected TextTango $client) {}

    public function enabled(string $event): bool
    {
        return (bool) Setting::get('sms_enabled', '1') && (bool) Setting::get('sms_on_'.$event, '1');
    }

    public function template(string $event): string
    {
        return (string) Setting::get('sms_tpl_'.$event, self::TEMPLATES[$event][1] ?? '');
    }

    public function render(string $template, array $vars): string
    {
        $vars['company'] ??= Setting::get('company_name', config('app.name'));
        $replace = [];
        foreach ($vars as $k => $v) {
            $replace['{'.$k.'}'] = (string) $v;
        }

        return trim(strtr($template, $replace));
    }

    /** Record and dispatch one SMS. Returns the log row (status queued/disabled/failed). */
    public function send(?string $to, string $message, string $type = 'manual', ?Model $related = null, ?int $userId = null, bool $force = false): ?SmsMessage
    {
        if (! $to) {
            return null;
        }

        $sms = SmsMessage::create([
            'to' => TextTango::normalizePhone($to),
            'message' => $message,
            'type' => $type,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'status' => 'queued',
            'sent_by' => $userId ?? auth()->id(),
        ]);

        if (! $force && ! (bool) Setting::get('sms_enabled', '1')) {
            $sms->update(['status' => 'disabled', 'error' => 'SMS notifications are switched off in Settings.']);

            return $sms;
        }

        if (! $this->client->isConfigured()) {
            $sms->update(['status' => 'failed', 'error' => 'TextTango API token is not configured.']);

            return $sms;
        }

        // Sent after the HTTP response so staff are never kept waiting on the SMS gateway.
        SendSms::dispatchAfterResponse($sms->id);

        return $sms;
    }

    protected function notify(string $event, ?string $phone, array $vars, ?Model $related): ?SmsMessage
    {
        if (! $this->enabled($event)) {
            return null;
        }

        return $this->send($phone, $this->render($this->template($event), $vars), $event, $related);
    }

    // ---------- Business events ----------

    public function collectionRecorded(SusuCollection $c): ?SmsMessage
    {
        $customer = $c->customer;

        return $this->notify('collection', $customer->phone, [
            'name' => $customer->name, 'amount' => Money::format($c->collected), 'date' => $c->date->format('d/m/Y'),
            'receipt' => $c->receipt_no, 'balance' => Money::format($customer->balance()), 'reference' => $c->receipt_no,
        ], $c);
    }

    public function withdrawalPaid(SusuWithdrawal $w): ?SmsMessage
    {
        $customer = $w->customer;

        return $this->notify('withdrawal', $customer->phone, [
            'name' => $customer->name, 'amount' => Money::format($w->amount), 'date' => $w->date->format('d/m/Y'),
            'reference' => $w->withdrawal_no, 'balance' => Money::format($w->balance_after),
        ], $w);
    }

    public function applicationDecided(LoanApplication $a): ?SmsMessage
    {
        $event = $a->status === 'Approved' ? 'loan_approved' : 'loan_declined';
        $totals = $a->approvedTotals();
        $period = Loan::periodLabel($a->approved_frequency ?? 'Weekly');

        return $this->notify($event, $a->borrower->phone, [
            'name' => $a->borrower->full_name, 'reference' => $a->application_no,
            'amount' => Money::format($a->approved_amount), 'total' => Money::format($totals['total']),
            'term' => $a->approved_term.' '.strtolower($period).'(s)',
        ], $a);
    }

    public function loanDisbursed(Loan $loan): ?SmsMessage
    {
        return $this->notify('loan_disbursed', $loan->borrower->phone, [
            'name' => $loan->borrower->full_name, 'reference' => $loan->loan_no,
            'amount' => Money::format($loan->principal), 'installment' => Money::format($loan->installment),
            'period' => strtolower(Loan::periodLabel($loan->frequency)), 'first_due' => $loan->first_due_date?->format('d/m/Y'),
            'total' => Money::format($loan->total_repayment), 'date' => $loan->disbursement_date->format('d/m/Y'),
        ], $loan);
    }

    public function repaymentReceived(LoanRepayment $r): ?SmsMessage
    {
        $loan = $r->loan;

        return $this->notify('repayment', $loan->borrower->phone, [
            'name' => $loan->borrower->full_name, 'reference' => $loan->loan_no, 'amount' => Money::format($r->amount),
            'date' => $r->date->format('d/m/Y'), 'balance' => Money::format($loan->outstanding()), 'receipt' => $r->receipt_no,
        ], $r);
    }

    public function repaymentReminder(Loan $loan, array $installment): ?SmsMessage
    {
        return $this->notify('reminder', $loan->borrower->phone, [
            'name' => $loan->borrower->full_name, 'reference' => $loan->loan_no,
            'amount' => Money::format($installment['balance']), 'date' => $installment['due_date']->format('d/m/Y'),
            'balance' => Money::format($loan->outstanding()),
        ], $loan);
    }

    public function overdueNotice(Loan $loan): ?SmsMessage
    {
        return $this->notify('overdue', $loan->borrower->phone, [
            'name' => $loan->borrower->full_name, 'reference' => $loan->loan_no,
            'amount' => Money::format($loan->arrears()), 'days' => $loan->daysOverdue(),
            'balance' => Money::format($loan->outstanding()),
        ], $loan);
    }
}
