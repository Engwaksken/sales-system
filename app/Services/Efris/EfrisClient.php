<?php

namespace App\Services\Efris;

use RuntimeException;

/**
 * Builds and sends the encrypted "envelope" the Uganda URA EFRIS API expects.
 *
 * Ported from the original process_efris_invoice.php:
 *   1. AES-256-CBC encrypt the JSON request body with a random 256-bit key.
 *   2. RSA (PKCS1) encrypt that AES key with URA's public key.
 *   3. RSA-SHA256 sign the AES-encrypted content with the taxpayer's private key.
 *   4. Wrap all three in the `data` / `globalInfo` / `returnStateInfo` envelope.
 *
 * Laravel's Crypt facade (AES-256-CBC over APP_KEY) already covers "encrypt secrets
 * at rest" via the EfrisConfig model's `encrypted` casts — this class only implements
 * the EFRIS wire-protocol crypto, which is a different, protocol-mandated scheme and
 * cannot be swapped for Laravel's encryption.
 */
class EfrisClient
{
    public function __construct(
        protected string $tin,
        protected string $deviceSerial,
        protected string $privatePem,
        protected string $uraPublicPem,
        protected string $environment = 'sandbox',
    ) {
    }

    public function baseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://efris.ura.go.ug/efrisws/ws/taInvoice/'
            : 'https://efristest.ura.go.ug/efrisws/ws/taInvoice/';
    }

    /**
     * Submit a T103 (invoice reporting) request. Returns [httpCode, decodedResponse, rawResponseBody, requestJson, latencyMs].
     */
    public function submitInvoice(array $t103Body): array
    {
        $envelope = $this->buildEnvelope($t103Body, 'T103');
        $requestJson = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';

        [$httpCode, $responseBody, $latencyMs] = $this->post($this->baseUrl(), $envelope);

        $resp = json_decode($responseBody, true);
        if (! is_array($resp)) {
            $resp = [];
        }

        return [$httpCode, $resp, $responseBody, $requestJson, $latencyMs];
    }

    public function buildEnvelope(array $requestBody, string $interfaceCode): array
    {
        $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
        if ($bodyJson === false) {
            throw new RuntimeException('Failed to encode EFRIS request payload.');
        }

        $aesKeyRaw = random_bytes(32);
        $content = $this->aesEncrypt($bodyJson, $aesKeyRaw);
        $encryptedKey = $this->rsaEncryptWithPublicKey($aesKeyRaw, $this->uraPublicPem);
        $signature = $this->rsaSign($content, $this->privatePem);

        return [
            'data' => [
                'content' => $content,
                'signature' => $signature,
                'dataDescription' => [
                    'codeType' => '0',
                    'encryptCode' => '1',
                    'zipCode' => '0',
                ],
            ],
            'globalInfo' => [
                'appId' => 'AP04',
                'version' => '1.1.20191201',
                'dataExchangeId' => now()->format('YmdHis').random_int(1000, 9999),
                'interfaceCode' => $interfaceCode,
                'requestCode' => 'TP',
                'requestTime' => now()->format('Y-m-d H:i:s'),
                'responseCode' => 'TA',
                'userName' => $this->tin,
                'deviceMAC' => 'FENXI',
                'deviceNo' => $this->deviceSerial,
                'tin' => $this->tin,
                'brn' => '',
                'taxpayerID' => '1',
                'longitude' => '32.61665',
                'latitude' => '0.31628',
                'agentType' => '0',
                'extendField' => [
                    'responsePaddingInfo' => '',
                    'encryptedKey' => $encryptedKey,
                ],
            ],
            'returnStateInfo' => [
                'returnCode' => '',
                'returnMessage' => '',
            ],
        ];
    }

    protected function aesEncrypt(string $plaintext, string $aesKeyRaw): string
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $aesKeyRaw, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new RuntimeException('AES encryption failed.');
        }

        return base64_encode($iv.$ciphertext);
    }

    protected function rsaEncryptWithPublicKey(string $plaintext, string $publicPem): string
    {
        $pub = openssl_pkey_get_public($publicPem);
        if (! $pub) {
            throw new RuntimeException('Invalid URA public key PEM.');
        }

        $encrypted = '';
        if (! openssl_public_encrypt($plaintext, $encrypted, $pub, OPENSSL_PKCS1_PADDING)) {
            throw new RuntimeException('RSA encryption failed.');
        }

        return base64_encode($encrypted);
    }

    protected function rsaSign(string $data, string $privatePem): string
    {
        $priv = openssl_pkey_get_private($privatePem);
        if (! $priv) {
            throw new RuntimeException('Invalid RSA private key PEM.');
        }

        $signature = '';
        if (! openssl_sign($data, $signature, $priv, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('RSA signing failed.');
        }

        return base64_encode($signature);
    }

    /** @return array{0: int, 1: string, 2: int} [httpCode, body, latencyMs] */
    protected function post(string $url, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode EFRIS request.');
        }

        $start = microtime(true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: '.strlen($json),
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        if ($body === false) {
            throw new RuntimeException('cURL error: '.$curlErr);
        }

        return [$httpCode, $body, $latencyMs];
    }

    /** Human-readable hint for a URA return code, matching the original's $hints table. */
    public static function hintForCode(string $code): ?string
    {
        return [
            '01' => 'TIN not found in URA registry.',
            '02' => 'Device serial number not registered.',
            '03' => 'Device is suspended or deactivated.',
            '04' => 'AES key exchange may be required first.',
            '05' => 'Signature verification failed. Check your RSA keys.',
            '06' => 'Decryption failed. Confirm URA public key.',
            '10' => 'System error on URA side. Retry later.',
            '11' => 'Interface not found. Check T103 configuration.',
            '99' => 'Authentication failed. Verify TIN, serial number, and keys.',
        ][$code] ?? null;
    }
}
