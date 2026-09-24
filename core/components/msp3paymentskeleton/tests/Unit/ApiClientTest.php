<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Tests\Unit;

use Msp3PaymentSkeleton\Api\ApiClient;
use Msp3PaymentSkeleton\Api\HttpTransport;
use PHPUnit\Framework\TestCase;

final class ApiClientTest extends TestCase
{
    public function testPrefixDependsOnTestMode(): void
    {
        self::assertSame('live', (new ApiClient('l', 's', false))->prefix());
        self::assertSame('test', (new ApiClient('l', 's', true))->prefix());
    }

    public function testCreatePaymentPostsToTestPrefix(): void
    {
        $seen = [];
        $transport = new class ($seen) implements HttpTransport {
            /** @param list<array{0: string, 1: string}> $seen */
            public function __construct(private array &$seen)
            {
            }

            public function request(string $method, string $url, ?array $body): array
            {
                $this->seen[] = [$method, $url];
                return ['status' => 200, 'json' => ['id' => 'pay_1', 'payment_link' => 'https://pay.example/1']];
            }
        };
        $client = new ApiClient('shop', 'secret', true, $transport);
        $json = $client->createPayment(['amount' => 100, 'currency' => 'TST']);
        self::assertSame('pay_1', $json['id']);
        self::assertStringContainsString('/test/payments', $seen[0][1]);
        $client->getPayment('pay_1');
        $client->listPayments();
        $client->refundPayment('pay_1', ['amount' => 50]);
        $client->cancelPayment('pay_1');
        self::assertCount(5, $seen);
    }
}
