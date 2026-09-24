<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Api;

interface HttpTransport
{
    /**
     * @param array<string, mixed>|null $body
     * @return array{status: int, json: array<string, mixed>}
     */
    public function request(string $method, string $url, ?array $body): array;
}
