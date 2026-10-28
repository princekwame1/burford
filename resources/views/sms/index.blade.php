<x-layouts.app title="SMS Centre">
    <x-page-header title="SMS Centre" subtitle="Every notification sent through TextTango, with delivery status.">
        <a href="{{ route('sms.compose') }}" class="btn btn-primary"><x-icon name="send" class="size-4" /> Compose</a>
    </x-page-header>

    @unless ($configured)
        <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center dark:border-amber-500/30 dark:bg-amber-500/10">
            <x-icon name="alert" class="size-6 text-amber-600" />
            <div class="flex-1 text-sm text-amber-900 dark:text-amber-100"><p class="font-semibold">TextTango is not connected</p><p>Messages are being logged but not delivered. Add your API token to start sending.</p></div>
            @if (auth()->user()->isAdmin())<a href="{{ route('settings.edit') }}#sms" class="btn btn-dark btn-sm">Connect TextTango</a>@endif
        </div>
    @endunless

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="TextTango balance" :value="$wallet && $wallet['ok'] ? $wallet['currency'].' '.number_format($wallet['balance'], 2) : '—'" icon="wallet" tone="brand" :sub="$wallet ? ($wallet['ok'] ? 'Bonus '.number_format($wallet['bonus'], 2) : $wallet['error']) : 'Not connected'" />
        <x-stat label="Sent (30 days)" :value="$stats['sent'] ?? 0" icon="check-circle" tone="blue" />
        <x-stat label="Failed (30 days)" :value="$stats['failed'] ?? 0" icon="alert" :tone="($stats['failed'] ?? 0) > 0 ? 'rose' : 'ink'" />
        <x-stat label="Queued / off (30 days)" :value="($stats['queued'] ?? 0) + ($stats['disabled'] ?? 0)" icon="clock" tone="ink" />
    </div>

    <div class="card mt-6">
        <x-filters :action="route('sms.index')" placeholder="Phone number or text…">
            <select name="status" class="input input-sm sm:w-36">
                <option value="">Any status</option>
                @foreach (['sent', 'failed', 'queued', 'disabled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <select name="type" class="input input-sm sm:w-44">
                <option value="">Any type</option>
                @foreach (array_merge(array_keys(\App\Services\SmsNotifier::TEMPLATES), ['manual', 'bulk']) as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucwords(str_replace('_', ' ', $t)) }}</option>@endforeach
            </select>
        </x-filters>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>When</th><th>To</th><th>Type</th><th>Message</th><th>Status</th><th>Sent by</th><th></th></tr></thead>
                <tbody>
                @forelse ($messages as $m)
                    <tr>
                        <td class="text-xs">{{ $m->created_at->format('d M, H:i') }}</td>
                        <td class="font-mono text-xs">{{ $m->to }}</td>
                        <td><span class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ str_replace('_', ' ', $m->type) }}</span></td>
                        <td class="max-w-md whitespace-normal text-xs text-ink-600 dark:text-ink-300">{{ $m->message }}
                            @if ($m->error)<p class="mt-1 font-medium text-rose-600">{{ $m->error }}</p>@endif</td>
                        <td><x-badge :status="$m->status" /></td>
                        <td class="text-xs">{{ $m->sender?->name ?? 'System' }}</td>
                        <td class="text-right">
                            @if (in_array($m->status, ['failed', 'disabled']))
                                <form method="POST" action="{{ route('sms.resend', $m) }}" class="inline" data-confirm-title="Resend this SMS?" data-confirm="It will be sent again to {{ $m->to }}." data-confirm-button="Resend">@csrf<button class="btn btn-ghost btn-sm" title="Resend"><x-icon name="refresh" class="size-3.5" /></button></form>
                            @endif
                            @if ($m->provider_uuid)
                                <form method="POST" action="{{ route('sms.status', $m) }}" class="inline">@csrf<button class="btn btn-ghost btn-sm" title="Check delivery status"><x-icon name="eye" class="size-3.5" /></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="message" title="No messages yet" text="Receipts, loan alerts and reminders will be logged here." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())<div class="border-t border-ink-100 p-4 dark:border-ink-800">{{ $messages->links() }}</div>@endif
    </div>
</x-layouts.app>
