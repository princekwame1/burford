<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Client for the TextTango v2 SMS API (https://app.texttango.com/docs/api/v2).
 *
 * Endpoints follow TextTango's own WordPress client:
 *   POST /v2/sms/transactional/send  {to, message, from?}
 *   GET  /v2/wallet
 *   GET  /v2/wallet/transactions?type=load_wallet
 *   GET  /v2/sender-ids
 *   GET  /v2/sms/messages/{uuid}
 *
 * Sends are never retried: the API has no idempotency key, so a retry after a
 * timeout could deliver (and bill) the same SMS twice.
 */
class TextTango
{
    public function __construct(
        protected ?string $token = null,
        protected ?string $baseUrl = null,
        protected ?string $senderId = null,
    ) {
        $this->token ??= (string) Setting::get('texttango_token', config('services.texttango.token'));
        $this->baseUrl ??= rtrim((string) Setting::get('texttango_base_url', config('services.texttango.base_url')), '/');
        $this->senderId ??= (string) Setting::get('texttango_sender_id', config('services.texttango.sender_id'));
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /** @return array{ok: bool, uuid: ?string, error: ?string, raw: array} */
    public function send(string $to, string $message, ?string $from = null): array
    {
        $payload = ['to' => static::normalizePhone($to), 'message' => $message];
        $from = $from ?: $this->senderId;
        if ($from) {
            $payload['from'] = $from;
        }

        try {
            $response = $this->http()->post('/v2/sms/transactional/send', $payload);
        } catch (ConnectionException $e) {
            return ['ok' => false, 'uuid' => null, 'error' => 'Could not reach TextTango: '.$e->getMessage(), 'raw' => []];
        }

        $data = $response->json() ?? [];
        if ($response->successful()) {
            $node = $data['data']['attributes'] ?? $data['data'] ?? $data;

            return ['ok' => true, 'uuid' => $node['uuid'] ?? $node['id'] ?? $node['message_id'] ?? null, 'error' => null, 'raw' => $data];
        }

        return ['ok' => false, 'uuid' => null, 'error' => $this->errorMessage($response), 'raw' => $data];
    }

    /** @return array{ok: bool, balance: float, bonus: float, currency: string, error: ?string} */
    public function wallet(): array
    {
        try {
            $response = $this->http()->retry(2, 500, throw: false)->get('/v2/wallet');
        } catch (ConnectionException $e) {
            return ['ok' => false, 'balance' => 0, 'bonus' => 0, 'currency' => 'GHS', 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'balance' => 0, 'bonus' => 0, 'currency' => 'GHS', 'error' => $this->errorMessage($response)];
        }

        $a = $response->json('data.attributes') ?? $response->json('data') ?? $response->json() ?? [];

        return [
            'ok' => true,
            'balance' => (float) ($a['main_balance'] ?? $a['balance'] ?? $a['main'] ?? $a['amount'] ?? 0),
            'bonus' => (float) ($a['bonus_balance'] ?? $a['bonus'] ?? 0),
            'currency' => strtoupper((string) ($a['currency'] ?? $a['currency_code'] ?? 'GHS')),
            'error' => null,
        ];
    }

    /**
     * Recent wallet top-ups (type load_wallet). The API cannot take payments, so money is
     * added on TextTango's own wallet page (topUpUrl()); this only reads the history back.
     *
     * @return array<int, array{date: ?string, amount: float, currency: string, status: ?string, reference: ?string}>
     */
    public function topUps(int $limit = 5): array
    {
        try {
            $response = $this->http()->get('/v2/wallet/transactions', ['type' => 'load_wallet', 'per_page' => $limit]);
        } catch (ConnectionException) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('data') ?? [])
            ->map(function ($item) {
                $a = $item['attributes'] ?? $item;

                return [
                    'date' => $a['created_at'] ?? $a['date'] ?? null,
                    'amount' => (float) ($a['amount'] ?? $a['total'] ?? 0),
                    'currency' => strtoupper((string) ($a['currency'] ?? 'GHS')),
                    'status' => $a['status'] ?? null,
                    'reference' => $a['reference'] ?? $a['transaction_ref'] ?? null,
                ];
            })
            ->take($limit)->values()->all();
    }

    /** TextTango's signed-in wallet page, where the account owner pays to add credit. */
    public static function topUpUrl(): string
    {
        return (string) Setting::get('texttango_topup_url', 'https://app.texttango.com/wallet');
    }

    /** @return array<int, string> Approved sender ID names. */
    public function senderIds(): array
    {
        try {
            $response = $this->http()->get('/v2/sender-ids');
        } catch (ConnectionException) {
            return [];
        }

        return collect($response->json('data') ?? [])
            ->map(fn ($item) => $item['attributes']['name'] ?? $item['attributes']['sender_id'] ?? $item['name'] ?? $item['sender_id'] ?? null)
            ->filter()->values()->all();
    }

    public function messageStatus(string $uuid): ?string
    {
        try {
            $response = $this->http()->get('/v2/sms/messages/'.rawurlencode($uuid));
        } catch (ConnectionException) {
            return null;
        }

        return $response->json('data.attributes.status') ?? $response->json('data.status') ?? $response->json('status');
    }

    /** Ghana numbers to E.164: 0241234567 → +233241234567. */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '+')) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+233'.substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+233'.$digits;
        }

        return '+'.$digits;
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->withoutRedirecting()
            ->withUserAgent(config('app.name').'/1.0');
    }

    protected function errorMessage(Response $response): string
    {
        $data = $response->json() ?? [];
        $first = $data['errors'][0] ?? null;
        $detail = is_array($first) ? ($first['detail'] ?? $first['title'] ?? null) : null;

        if (! $detail && isset($data['errors']) && is_array($data['errors'])) {
            $fieldErrors = collect($data['errors'])->flatten()->filter(fn ($v) => is_string($v));
            $detail = $fieldErrors->first();
        }

        $detail ??= $data['message'] ?? null;

        return $detail ?: match ($response->status()) {
            401 => 'The API token was rejected (invalid, revoked or missing abilities).',
            402 => 'TextTango balance is too low. Top up to resume sending.',
            403 => 'The API token does not have the abilities this action requires.',
            429 => 'TextTango is rate limiting requests. Try again shortly.',
            default => 'TextTango request failed (HTTP '.$response->status().').',
        };
    }
}
