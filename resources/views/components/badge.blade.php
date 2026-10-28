@props(['status' => null, 'tone' => null])
@php
    $map = [
        'green' => ['Paid', 'Active', 'Approved', 'Reconciled', 'sent', 'Closed', 'Disbursed'],
        'amber' => ['Partial', 'Pending', 'Open', 'queued', 'Dormant', 'Overdue', 'Escalated'],
        'rose' => ['Unpaid', 'Declined', 'Defaulted', 'failed', 'Suspended', 'Missed'],
        'ink' => ['Inactive', 'disabled', 'Upcoming'],
    ];
    $tone ??= collect($map)->search(fn ($list) => in_array($status, $list, true)) ?: 'ink';
    if ($status === 'Closed' || $status === 'Disbursed') { $tone = 'blue'; }
    $classes = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/20',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/15 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/15 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-400/20',
        'ink' => 'bg-ink-100 text-ink-600 ring-ink-500/15 dark:bg-ink-800 dark:text-ink-300 dark:ring-ink-600/30',
    ];
    $dot = ['green' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500', 'blue' => 'bg-sky-500', 'violet' => 'bg-violet-500', 'ink' => 'bg-ink-400'];
@endphp
<span {{ $attributes->merge(['class' => 'badge '.$classes[$tone]]) }}>
    <span class="size-1.5 rounded-full {{ $dot[$tone] }}"></span>{{ $slot->isEmpty() ? ucfirst((string) $status) : $slot }}
</span>
