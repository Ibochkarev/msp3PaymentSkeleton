<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

final class Money
{
    public static function toKopecks(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromKopecks(int $kopecks): float
    {
        return round($kopecks / 100, 2);
    }
}
