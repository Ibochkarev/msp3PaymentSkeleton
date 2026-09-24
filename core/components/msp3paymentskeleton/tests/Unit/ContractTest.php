<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Tests\Unit;

use MiniShop3\Controllers\Payment\PaymentProviderInterface;
use MiniShop3\Controllers\Payment\PaymentWebhookHandlerInterface;
use MiniShop3\Services\Payment\PaymentAttemptStatus;
use Msp3PaymentSkeleton\Api\WebhookParser;
use Msp3PaymentSkeleton\Payment\SkeletonPayment;
use Msp3PaymentSkeleton\Processors\Mgr\BaseProcessor;
use Msp3PaymentSkeleton\Processors\Mgr\RefundProcessor;
use Msp3PaymentSkeleton\Service\AttemptReader;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class ContractTest extends TestCase
{
    public function testHandlerImplementsMs3Contracts(): void
    {
        $implements = class_implements(SkeletonPayment::class) ?: [];
        if (!interface_exists(PaymentWebhookHandlerInterface::class)) {
            self::assertTrue(method_exists(SkeletonPayment::class, 'verifyWebhook'));
            self::assertTrue(method_exists(SkeletonPayment::class, 'parseWebhook'));
            self::assertTrue(method_exists(SkeletonPayment::class, 'send'));
            self::assertTrue(method_exists(SkeletonPayment::class, 'receive'));
            return;
        }
        self::assertContains(PaymentWebhookHandlerInterface::class, $implements);
        self::assertTrue(
            is_subclass_of(SkeletonPayment::class, PaymentProviderInterface::class)
            || in_array(PaymentProviderInterface::class, $implements, true)
            || method_exists(SkeletonPayment::class, 'send')
        );
    }

    public function testRequiredSendKeys(): void
    {
        $keys = SkeletonPayment::requiredSendKeys();
        foreach (['payment_link', 'payment_id', 'external_id', 'currency'] as $key) {
            self::assertContains($key, $keys);
        }
    }

    public function testParseWebhookSignature(): void
    {
        $method = (new ReflectionClass(SkeletonPayment::class))->getMethod('parseWebhook');
        $return = $method->getReturnType();
        self::assertInstanceOf(ReflectionNamedType::class, $return);
        self::assertTrue($return->allowsNull());
    }

    public function testCheckPermissionsIsPublic(): void
    {
        $method = (new ReflectionClass(BaseProcessor::class))->getMethod('checkPermissions');
        self::assertTrue($method->isPublic());
        $init = (new ReflectionClass(BaseProcessor::class))->getMethod('initialize');
        self::assertTrue($init->isPublic());
    }

    /**
     * @dataProvider refundBlockLexiconCases
     */
    public function testRefundBlockLexiconBeforeApi(string $status, ?string $expectedLexiconKey): void
    {
        self::assertSame($expectedLexiconKey, RefundProcessor::refundBlockLexicon($status));
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function refundBlockLexiconCases(): array
    {
        return [
            'paid' => [PaymentAttemptStatus::PAID, null],
            'partial' => [PaymentAttemptStatus::PARTIALLY_REFUNDED, null],
            'pending' => [PaymentAttemptStatus::PENDING, 'msp3paymentskeleton.err_refund_pending'],
            'authorized' => [PaymentAttemptStatus::AUTHORIZED, 'msp3paymentskeleton.err_refund_hold'],
            'refunded' => [PaymentAttemptStatus::REFUNDED, 'msp3paymentskeleton.err_refund_done'],
            'failed' => [PaymentAttemptStatus::FAILED, 'msp3paymentskeleton.err_refund_status'],
        ];
    }

    public function testProviderRefundIdReadsCreatePayload(): void
    {
        self::assertSame('op-1', AttemptReader::providerRefundId([
            'external_id' => 'inv-1',
            'payload' => ['operation_id' => 'op-1', 'payment_link' => 'https://pay.example/1'],
        ]));
        self::assertSame('inv-1', AttemptReader::providerRefundId([
            'external_id' => 'inv-1',
            'payload' => ['payment_link' => 'https://pay.example/1'],
        ]));
        self::assertSame('', AttemptReader::providerRefundId([
            'payload' => ['payment_link' => 'https://pay.example/1'],
        ]));
    }

    public function testBlockedPayloadKeysMatchLifecycle(): void
    {
        $blocked = ['password', 'secret', 'token', 'api_key', 'secret_key', 'properties', 'class', 'authorization'];
        $clean = WebhookParser::safePayload(array_fill_keys($blocked, 'x') + ['event' => 'paid', 'payment' => ['id' => '1']]);
        foreach ($blocked as $key) {
            self::assertArrayNotHasKey($key, $clean);
        }
    }
}
