<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Services\Payment\PaymentLifecycleService;
use MODX\Revolution\modX;
use Msp3PaymentSkeleton\Payment\SkeletonPayment;

class AttemptReader
{
    /**
     * @return list<class-string>
     */
    public static function providerClasses(): array
    {
        $classes = [SkeletonPayment::class];
        $second = 'Msp3PaymentSkeleton\\Payment\\SkeletonSecondPayment';
        if (class_exists($second)) {
            $classes[] = $second;
        }

        return $classes;
    }

    public function __construct(private readonly modX $modx)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForOrder(int $orderId): array
    {
        $table = $this->modx->getOption('table_prefix') . 'ms3_payment_attempts';
        $quoted = array_map(fn (string $class) => $this->modx->quote($class), self::providerClasses());
        $sql = 'SELECT * FROM `' . $table . '`'
            . ' WHERE `order_id` = ' . $orderId
            . ' AND `provider` IN (' . implode(', ', $quoted) . ') ORDER BY `id` DESC';
        $result = $this->modx->query($sql);
        if ($result === false) {
            return [];
        }

        return array_map([$this, 'normalize'], $result->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestForOrder(int $orderId): ?array
    {
        $rows = $this->listForOrder($orderId);

        return $rows[0] ?? null;
    }

    public function storedLink(int $orderId): ?string
    {
        if (!$this->modx->services->has('ms3_payment_lifecycle')) {
            return null;
        }
        $lifecycle = $this->modx->services->get('ms3_payment_lifecycle');
        if (!$lifecycle instanceof PaymentLifecycleService) {
            return null;
        }

        return $lifecycle->storedPaymentLink($orderId);
    }

    /**
     * Extra ids from initiate() (operationId, invoice_id, mdOrder), else external_id.
     *
     * @param array<string, mixed> $attempt
     */
    public static function providerRefundId(array $attempt): string
    {
        $payload = is_array($attempt['payload'] ?? null) ? $attempt['payload'] : [];
        foreach (['provider_payment_id', 'operation_id', 'operationId', 'invoice_id', 'mdOrder', 'id'] as $key) {
            $value = self::scalarString($payload[$key] ?? '');
            if ($value !== '') {
                return $value;
            }
        }

        return self::scalarString($attempt['external_id'] ?? '');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $payload = $row['payload'] ?? [];
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        $row['payload'] = is_array($payload) ? $payload : [];
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['order_id'] = (int) ($row['order_id'] ?? 0);
        $row['amount'] = (float) ($row['amount'] ?? 0);
        $row['refunded_amount'] = (float) ($row['refunded_amount'] ?? 0);

        return $row;
    }

    private static function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
