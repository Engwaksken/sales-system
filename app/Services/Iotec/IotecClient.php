<?php

namespace App\Services\Iotec;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to the IOTEC mobile-money collection API using the OAuth2 client-credentials
 * flow described in payment_gateway_helper.php / check_payment_status.php (the
 * `payment_gateways` table's token_url + client_id/client_secret + collect_url +
 * status_url columns).
 *
 * Note on the original codebase: iotec_collect.php implemented a *different*,
 * username/password-based auth flow against a `payment_settings` table
 * (base_url + '/auth/token' with iotec_username/iotec_password). That appears to be
 * an older/abandoned code path — check_payment_status.php (the most recently modified
 * file, and the one referenced by the payment_gateways-driven admin UI) uses the
 * client_id/client_secret flow below, so that's what this port standardizes on.
 */
class IotecClient
{
    protected ?string $cachedToken = null;

    public function __construct(protected PaymentGateway $gateway)
    {
    }

    public static function forDefaultGateway(): self
    {
        $gateway = PaymentGateway::where('gateway_code', 'iotec')
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            throw new RuntimeException('No active IOTEC gateway configured. Set one up under Payment Gateways.');
        }

        return new self($gateway);
    }

    /** Step 1: obtain an OAuth2 access token via client_credentials. */
    public function getAccessToken(): string
    {
        if ($this->cachedToken) {
            return $this->cachedToken;
        }

        $tokenUrl = trim((string) $this->gateway->token_url);
        $clientId = trim((string) $this->gateway->client_id);
        $clientSecret = trim((string) $this->gateway->client_secret);

        if ($tokenUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('IOTEC gateway is missing token_url/client_id/client_secret.');
        }

        $response = Http::asJson()
            ->timeout(20)
            ->post($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Could not connect to IOTEC token endpoint: HTTP '.$response->status());
        }

        $token = $response->json('access_token') ?? $response->json('token');
        if (! $token) {
            throw new RuntimeException('IOTEC did not return an access_token.');
        }

        return $this->cachedToken = $token;
    }

    /**
     * Step 2: initiate a mobile-money collection (STK push).
     *
     * @return array{status: string, reference: ?string, message: string, raw: array}
     */
    public function initiateCollection(string $phone, float $amount, string $network, string $reference, string $description): array
    {
        $collectUrl = trim((string) $this->gateway->collect_url);
        if ($collectUrl === '') {
            throw new RuntimeException('IOTEC gateway is missing collect_url.');
        }

        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->asJson()
            ->timeout(30)
            ->post($collectUrl, [
                'msisdn' => $phone,
                'amount' => (int) round($amount),
                'currency' => 'UGX',
                'network' => strtoupper($network),
                'reference' => $reference,
                'description' => $description,
                'callbackUrl' => route('iotec.webhook'),
            ]);

        $body = $response->json() ?? [];
        $gatewayStatus = strtolower((string) ($body['status'] ?? $body['code'] ?? ''));
        $txId = (string) ($body['transactionId'] ?? $body['reference'] ?? $body['id'] ?? '');
        $message = (string) ($body['message'] ?? $body['msg'] ?? 'No message');

        $okStatuses = ['success', 'ok', 'accepted', 'pending', '200'];
        $ourStatus = in_array($gatewayStatus, $okStatuses, true) || $response->successful() ? 'pending' : 'failed';

        return [
            'status' => $ourStatus,
            'reference' => $txId ?: null,
            'message' => $message,
            'raw' => $body,
        ];
    }

    /**
     * Step 3: poll the status of a previously-initiated collection.
     *
     * @return array{gateway_status: string, reference: ?string, message: ?string, raw: array}
     */
    public function checkStatus(string $gatewayReference): array
    {
        $statusUrl = trim((string) $this->gateway->status_url);
        if ($statusUrl === '') {
            throw new RuntimeException('IOTEC gateway is missing status_url.');
        }

        $token = $this->getAccessToken();
        $url = rtrim($statusUrl, '/').'/'.rawurlencode($gatewayReference);

        $response = Http::withToken($token)->asJson()->timeout(30)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to reach IOTEC status endpoint: HTTP '.$response->status());
        }

        $body = $response->json() ?? [];

        $reference = (string) ($body['transactionId'] ?? $body['reference'] ?? $body['requestId'] ?? $gatewayReference);
        $gatewayStatus = strtoupper(trim((string) ($body['status'] ?? 'PENDING'))) ?: 'PENDING';
        $message = (string) ($body['message'] ?? $body['description'] ?? 'Payment status checked successfully.');

        return [
            'gateway_status' => $gatewayStatus,
            'reference' => $reference ?: null,
            'message' => $message ?: null,
            'raw' => $body,
        ];
    }

    /** Maps a raw gateway status string to our internal payment_status values. */
    public static function mapPaymentStatus(string $gatewayStatus): string
    {
        $status = strtoupper(trim($gatewayStatus));

        return match (true) {
            in_array($status, ['SUCCESS', 'SUCCEEDED', 'COMPLETED', 'COMPLETE', 'PAID'], true) => 'paid',
            in_array($status, ['FAILED', 'FAIL', 'CANCELLED', 'CANCELED', 'REJECTED', 'DECLINED', 'ERROR'], true) => 'failed',
            default => 'pending',
        };
    }

    /**
     * Substring-based status normalizer, matching the original iotec_webhook.php's
     * normalize_status() (looser matching than the exact-match version above, since
     * webhook payloads from the gateway are less predictable than polled responses).
     */
    public static function normalizeWebhookStatus(string $status): string
    {
        $s = strtolower(trim($status));

        if (str_contains($s, 'success') || str_contains($s, 'paid') || str_contains($s, 'completed')) {
            return 'paid';
        }

        if (str_contains($s, 'fail') || str_contains($s, 'cancel') || str_contains($s, 'reject')) {
            return 'failed';
        }

        return 'pending';
    }
}
