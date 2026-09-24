<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Tests\Unit;

use Msp3PaymentSkeleton\Api\Signature;
use PHPUnit\Framework\TestCase;

final class SignatureTest extends TestCase
{
    public function testHmacIsStable(): void
    {
        $body = '{"event":"payment.succeeded"}';
        $sig = Signature::hmac($body, 'secret_key_123');
        self::assertSame(64, strlen($sig));
        self::assertTrue(Signature::verify($body, 'secret_key_123', $sig));
        self::assertFalse(Signature::verify($body, 'other', $sig));
        self::assertFalse(Signature::verify('', 'secret_key_123', $sig));
    }
}
