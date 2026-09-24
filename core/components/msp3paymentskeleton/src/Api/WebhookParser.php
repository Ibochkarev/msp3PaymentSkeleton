<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

/**
 * PROVIDER: map the provider payload onto MiniShop3 attempt statuses.
 */
final class WebhookParser
{
    public const KIND_PAID = 'paid';
    public const KIND_AUTHORIZED = 'authorized';
    public const KIND_FAILED = 'failed';
    public const KIND_CANCELLED = 'cancelled';
    public const KIND_REFUND = 'refunded';
    public const KIND_UNKNOWN = 'unknown';

    /**
     * @param array<string, mixed> $payload
     */
    public static function kind(array $payload): string
    {
        $event = strtolower((string) ($payload['event'] ?? $payload['type'] ?? ''));
        $status = strtolower((string) (self::paymentObject($payload)['status'] ?? ''));

        return match (true) {
            str_contains($event, 'refund') || $status === 'refunded' => self::KIND_REFUND,
            str_contains($event, 'fail') || $status === 'failed' => self::KIND_FAILED,
            str_contains($event, 'cancel') || $status === 'cancelled' => self::KIND_CANCELLED,
            str_contains($event, 'authoriz') || $status === 'authorized' => self::KIND_AUTHORIZED,
            str_contains($event, 'succeed') || str_contains($event, 'paid') || $status === 'succeeded' || $status === 'paid' => self::KIND_PAID,
            default => self::KIND_UNKNOWN,
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public static function paymentObject(array $payload): ?array
    {
        $payment = $payload['payment'] ?? $payload['object'] ?? null;

        return is_array($payment) ? $payment : null;
    }

    /**
     * Id stored as attempt.external_id after send().
     * If the cabinet POSTs another id (PayKeeper payment id vs invoice_id),
     * leave this empty and let WebhookHandler fill it from the order attempt.
     *
     * @param array<string, mixed> $payload
     */
    public static function externalId(array $payload): string
    {
        $payment = self::paymentObject($payload);
        $id = $payment['id'] ?? $payload['payment_id'] ?? $payload['invoice_id'] ?? '';

        return is_scalar($id) ? (string) $id : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function orderId(array $payload): ?int
    {
        $meta = $payload['metadata'] ?? [];
        $candidates = [
            is_array($meta) ? ($meta['order_id'] ?? null) : null,
            $payload['order_id'] ?? null,
            $payload['orderid'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            $orderId = self::positiveInt($candidate);
            if ($orderId !== null) {
                return $orderId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function currency(array $payload): string
    {
        $payment = self::paymentObject($payload);
        $currency = $payment['currency'] ?? $payload['currency'] ?? '';

        return is_string($currency) ? $currency : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function providerEventId(array $payload): string
    {
        $id = $payload['id'] ?? $payload['event_id'] ?? '';
        if (is_int($id) || is_float($id)) {
            return (string) $id;
        }

        return is_string($id) ? $id : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function amountRubles(array $payload): ?float
    {
        $payment = self::paymentObject($payload);
        $amount = $payment['amount'] ?? $payload['amount'] ?? $payment['refund_amount'] ?? null;
        if ($amount === null) {
            return null;
        }

        return Money::fromKopecks((int) $amount);
    }

    /**
     * Drop credentials before storing the event on ms3_payment_attempts.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function safePayload(array $payload): array
    {
        $blocked = ['password', 'secret', 'token', 'api_key', 'secret_key', 'properties', 'class', 'authorization'];
        $clean = array_diff_key($payload, array_flip($blocked));
        if (isset($clean['payment']) && is_array($clean['payment'])) {
            $clean['payment'] = array_diff_key($clean['payment'], array_flip($blocked));
        }

        return $clean;
    }

    private static function positiveInt(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
