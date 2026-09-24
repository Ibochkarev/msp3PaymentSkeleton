<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

use Msp3PaymentSkeleton\Exception\ProviderException;

final class StreamHttpTransport implements HttpTransport
{
    /**
     * @param array<string, mixed>|null $body
     * @return array{status: int, json: array<string, mixed>}
     */
    public function request(string $method, string $url, ?array $body): array
    {
        self::assertHttpUrl($url);
        $headers = "Accept: application/json\r\n";
        $content = '';
        if ($body !== null) {
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new ProviderException('Unable to encode request JSON');
            }
            $content = $encoded;
            $headers .= "Content-Type: application/json\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $content,
                'timeout' => 30,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
        ]);

        $raw = file_get_contents($url, false, $context);
        $status = 0;
        $responseHeaders = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : ($GLOBALS['http_response_header'] ?? null);
        if (is_array($responseHeaders)) {
            foreach ($responseHeaders as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', (string) $line, $m)) {
                    $status = (int) $m[1];
                }
            }
        }
        if ($raw === false) {
            throw new ProviderException('Payment provider HTTP error');
        }

        $decoded = $raw === '' ? [] : json_decode($raw, true);
        if ($raw !== '' && !is_array($decoded)) {
            throw new ProviderException('Payment provider returned invalid JSON', '', $status);
        }

        return [
            'status' => $status,
            'json' => is_array($decoded) ? $decoded : [],
        ];
    }

    private static function assertHttpUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['https', 'http'], true) || ($parts['host'] ?? '') === '') {
            throw new ProviderException('Refusing non-HTTP payment URL');
        }
    }
}
