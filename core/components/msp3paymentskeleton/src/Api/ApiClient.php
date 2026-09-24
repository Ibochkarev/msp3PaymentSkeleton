<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

use Msp3PaymentSkeleton\Exception\ProviderException;

/**
 * PROVIDER: replace host, paths and request shape with the merchant API.
 */
class ApiClient
{
    // PROVIDER: production host
    public const HOST = 'https://example-pay.test';

    public function __construct(
        private readonly string $login,
        private readonly string $secret,
        private readonly bool $testMode = false,
        private readonly ?HttpTransport $transport = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    public function createPayment(array $payment): array
    {
        return $this->send('POST', 'payments', $payment);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->send('GET', 'payments/' . rawurlencode($paymentId), null);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listPayments(array $query = []): array
    {
        return $this->send('GET', 'payments', $query === [] ? null : $query);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function refundPayment(string $paymentId, array $data): array
    {
        return $this->send('POST', 'payments/' . rawurlencode($paymentId) . '/refund', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelPayment(string $paymentId): array
    {
        return $this->send('POST', 'payments/' . rawurlencode($paymentId) . '/cancel', []);
    }

    public function prefix(): string
    {
        // PROVIDER: test vs live path prefix
        return $this->testMode ? 'test' : 'live';
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, ?array $body): array
    {
        $url = rtrim(self::HOST, '/') . '/' . $this->prefix() . '/' . ltrim($path, '/');
        $payload = $body;
        if ($payload !== null) {
            $payload['login'] = $this->login;
            $payload['signature'] = Signature::hmac(
                (string) json_encode($body, JSON_UNESCAPED_UNICODE),
                $this->secret
            );
        }

        $transport = $this->transport ?? new StreamHttpTransport();
        $response = $transport->request($method, $url, $payload);
        $status = $response['status'];
        $decoded = $response['json'];

        if ($status >= 400) {
            $message = (string) ($decoded['message'] ?? $decoded['error'] ?? ('HTTP ' . $status));
            $code = (string) ($decoded['code'] ?? (string) $status);
            throw new ProviderException($message, $code, $status);
        }

        return $decoded;
    }
}
