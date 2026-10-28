<?php

use App\Models\Loan;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Services\SmsNotifier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('loans:refresh-status', function () {
    $changed = 0;
    Loan::open()->get()->each(function (Loan $loan) use (&$changed) {
        $before = $loan->status;
        $loan->refreshStatus();
        $changed += (int) ($before !== $loan->status);
    });
    $this->info("Loan statuses refreshed ($changed changed).");
})->purpose('Mark loans Active / Overdue / Closed based on the repayment schedule');

Artisan::command('sms:loan-reminders', function (SmsNotifier $sms) {
    $daysBefore = (int) Setting::get('reminder_days_before', 1);
    $target = today()->addDays($daysBefore);
    $reminders = $overdue = 0;

    Loan::with('borrower')->open()->get()->each(function (Loan $loan) use ($sms, $target, &$reminders, &$overdue) {
        // Never message the same borrower about the same loan twice in one day.
        $alreadyToday = SmsMessage::where('related_type', $loan->getMorphClass())->where('related_id', $loan->id)
            ->whereIn('type', ['reminder', 'overdue'])->whereDate('created_at', today())->exists();
        if ($alreadyToday) {
            return;
        }

        if ($loan->arrears() > 0.009) {
            // Overdue notices go out on day 1, 3, 7, then weekly, to avoid spamming.
            $days = $loan->daysOverdue();
            if (in_array($days, [1, 3, 7], true) || ($days > 7 && $days % 7 === 0)) {
                $overdue += (int) (bool) $sms->overdueNotice($loan);
            }

            return;
        }

        $next = $loan->nextInstallment();
        if ($next && $next['due_date']->isSameDay($target)) {
            $reminders += (int) (bool) $sms->repaymentReminder($loan, $next);
        }
    });

    $this->info("Queued $reminders reminder(s) and $overdue overdue notice(s).");
})->purpose('Send TextTango repayment reminders and overdue notices');

Schedule::command('loans:refresh-status')->dailyAt('00:30');
Schedule::command('sms:loan-reminders')->dailyAt('08:00');
