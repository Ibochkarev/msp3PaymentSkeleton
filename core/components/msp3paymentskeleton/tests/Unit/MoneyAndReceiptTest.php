<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Tests\Unit;

use Msp3PaymentSkeleton\Api\Money;
use Msp3PaymentSkeleton\Service\CustomerContacts;
use Msp3PaymentSkeleton\Service\ReceiptBuilder;
use PHPUnit\Framework\TestCase;

final class MoneyAndReceiptTest extends TestCase
{
    public function testKopecksRoundTrip(): void
    {
        self::assertSame(1999, Money::toKopecks(19.99));
        self::assertSame(19.99, Money::fromKopecks(1999));
    }

    public function testAlignSumFixesLastItem(): void
    {
        $items = [
            ['id' => 'a', 'sum' => 1000, 'price' => 1000, 'quantity' => 1],
            ['id' => 'b', 'sum' => 500, 'price' => 500, 'quantity' => 1],
        ];
        $aligned = ReceiptBuilder::alignSum($items, 1600);
        self::assertSame(600, $aligned[1]['sum']);
    }

    public function testPhoneNormalize(): void
    {
        self::assertSame('79991234567', CustomerContacts::normalizePhone('+7 (999) 123-45-67'));
        self::assertSame('79991234567', CustomerContacts::normalizePhone('8 999 123-45-67'));
        self::assertSame('', CustomerContacts::normalizePhone('123'));
    }
}
