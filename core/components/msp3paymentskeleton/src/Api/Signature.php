<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

/**
 * PROVIDER: replace HMAC if the provider uses another algorithm.
 * MiniShop3 ships PaymentWebhookHmac with the same HMAC-SHA256 hex digest.
 */
final class Signature
{
    public static function hmac(string $rawBody, string $secret): string
    {
        return hash_hmac('sha256', $rawBody, $secret);
    }

    public static function verify(string $rawBody, string $secret, string $signature): bool
    {
        if ($rawBody === '' || $secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(self::hmac($rawBody, $secret), strtolower($signature));
    }
}
