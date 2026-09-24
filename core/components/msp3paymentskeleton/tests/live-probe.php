<?php

declare(strict_types=1);

/**
 * Probe the merchant API with your cabinet keys.
 * Example host in ApiClient is a placeholder and will fail until you replace PROVIDER: blocks.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Msp3PaymentSkeleton\Api\ApiClient;
use Msp3PaymentSkeleton\Exception\ProviderException;

$login = getenv('PAYMENTSKELETON_LOGIN') ?: 'test';
$secret = getenv('PAYMENTSKELETON_SECRET') ?: 'secret_key_123';

$client = new ApiClient($login, $secret, true);
echo "=== createPayment test prefix ===\n";
try {
    echo json_encode($client->createPayment([
        'amount' => 100,
        'currency' => 'TST',
        'description' => 'Skeleton probe',
    ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
} catch (ProviderException $e) {
    echo 'HTTP ' . $e->getHttpStatus() . ' message=' . $e->getMessage() . "\n";
}
