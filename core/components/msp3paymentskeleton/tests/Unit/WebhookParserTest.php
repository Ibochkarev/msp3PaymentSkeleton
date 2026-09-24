<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Tests\Unit;

use Msp3PaymentSkeleton\Api\WebhookParser;
use PHPUnit\Framework\TestCase;

final class WebhookParserTest extends TestCase
{
    public function testFixtures(): void
    {
        $paid = json_decode((string) file_get_contents(dirname(__DIR__) . '/Fixtures/webhook-paid.json'), true);
        $refund = json_decode((string) file_get_contents(dirname(__DIR__) . '/Fixtures/webhook-refund.json'), true);
        self::assertSame(WebhookParser::KIND_PAID, WebhookParser::kind($paid));
        self::assertSame(WebhookParser::KIND_REFUND, WebhookParser::kind($refund));
        self::assertSame('pay_abc', WebhookParser::externalId($paid));
        self::assertSame(10.0, WebhookParser::amountRubles($paid));
        self::assertSame('evt_1', WebhookParser::providerEventId($paid));
        self::assertNull(WebhookParser::orderId($paid));
        self::assertSame(42, WebhookParser::orderId([
            'orderid' => '42',
            'metadata' => ['order_id' => 42],
        ]));
    }

    public function testSafePayloadDropsSecrets(): void
    {
        $clean = WebhookParser::safePayload([
            'event' => 'payment.succeeded',
            'secret_key' => 'x',
            'token' => 'y',
            'payment' => ['id' => 'pay_1', 'secret' => 'z', 'amount' => 100],
        ]);
        self::assertArrayNotHasKey('secret_key', $clean);
        self::assertArrayNotHasKey('token', $clean);
        self::assertArrayNotHasKey('secret', $clean['payment']);
        self::assertSame('pay_1', $clean['payment']['id']);
    }

    public function testKindMatchesLifecycleStatuses(): void
    {
        self::assertSame('paid', WebhookParser::kind(['event' => 'payment.paid']));
        self::assertSame('authorized', WebhookParser::kind(['event' => 'payment.authorized']));
        self::assertSame('failed', WebhookParser::kind(['event' => 'payment.failed']));
        self::assertSame('cancelled', WebhookParser::kind(['event' => 'payment.cancelled']));
        self::assertSame('refunded', WebhookParser::kind(['event' => 'refund.succeeded']));
    }
}
