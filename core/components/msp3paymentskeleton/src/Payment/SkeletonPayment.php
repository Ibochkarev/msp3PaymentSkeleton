<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Payment;

use MiniShop3\Controllers\Payment\Payment;
use MiniShop3\Controllers\Payment\PaymentWebhookEvent;
use MiniShop3\Controllers\Payment\PaymentWebhookHandlerInterface;
use MiniShop3\Model\msOrder;
use MiniShop3\Model\msPayment;
use MiniShop3\Services\Payment\PaymentAttemptStatus;
use MiniShop3\Services\Payment\PaymentLifecycleService;
use Msp3PaymentSkeleton\Api\Money;
use Msp3PaymentSkeleton\Api\WebhookParser;
use Msp3PaymentSkeleton\Service\CustomerContacts;
use Msp3PaymentSkeleton\Service\PackageLogger;
use Msp3PaymentSkeleton\Service\ReceiptBuilder;
use Msp3PaymentSkeleton\Service\Settings;

class SkeletonPayment extends Payment implements PaymentWebhookHandlerInterface
{
    /**
     * @return list<string>
     */
    public static function requiredSendKeys(): array
    {
        return ['payment_link', 'payment_id', 'external_id', 'currency'];
    }

    public function send(msOrder $order): array
    {
        $settings = $this->settings();
        $logger = new PackageLogger($this->modx);
        if ($settings->login() === '' || $settings->secret() === '') {
            $logger->error('Payment Skeleton is not configured');
            return $this->error('Payment is not configured');
        }

        $cost = (float) $order->get('cost');
        if ($cost < 1) {
            return $this->error('Invalid order amount');
        }

        $request = $this->buildPaymentRequest($order, $settings, $logger);
        $logger->debug('createPayment request', ['payload' => $request]);

        try {
            $response = $settings->client()->createPayment($request);
        } catch (\Throwable $e) {
            $logger->error('createPayment failed: ' . $e->getMessage(), [
                'order_num' => $order->get('num'),
            ]);
            return $this->error($e->getMessage());
        }

        $link = (string) ($response['confirmation_url'] ?? $response['payment_link'] ?? $response['link'] ?? '');
        $paymentId = (string) ($response['id'] ?? $response['payment_id'] ?? '');
        if ($link === '' || $paymentId === '') {
            $logger->error('No payment_link or payment_id in response');
            return $this->error('Payment URL not received');
        }

        $this->persistCreateExtras($order, $paymentId, $link, $response);

        return $this->success('', [
            'redirect' => $link,
            'payment_link' => $link,
            'payment_id' => $paymentId,
            'external_id' => $paymentId,
            'currency' => $settings->currency(),
            'order_id' => (int) $order->get('id'),
            'order_num' => (string) $order->get('num'),
            'msorder' => (string) $order->get('uuid'),
        ]);
    }

    public function receive(msOrder $order): array
    {
        return $this->success('ms3_payment_received', [
            'order_id' => $order->get('id'),
            'order_num' => $order->get('num'),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function verifyWebhook(string $rawBody, array $payload, array $headers, msPayment $method): bool
    {
        $settings = $this->settings()->withMethod($method);
        $secret = $settings->webhookSecret();
        $signature = $this->headerSignature($headers, $payload);
        if ($secret === '' || $signature === '') {
            return false;
        }
        return $this->verifyWebhookHmac($rawBody, $signature, $secret);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function parseWebhook(array $payload, array $headers): ?PaymentWebhookEvent
    {
        $kind = WebhookParser::kind($payload);
        $externalId = WebhookParser::externalId($payload);
        if ($externalId === '' || $kind === WebhookParser::KIND_UNKNOWN) {
            return null;
        }

        $eventType = match ($kind) {
            WebhookParser::KIND_PAID => PaymentAttemptStatus::PAID,
            WebhookParser::KIND_AUTHORIZED => PaymentAttemptStatus::AUTHORIZED,
            WebhookParser::KIND_FAILED => PaymentAttemptStatus::FAILED,
            WebhookParser::KIND_CANCELLED => PaymentAttemptStatus::CANCELLED,
            WebhookParser::KIND_REFUND => PaymentAttemptStatus::REFUNDED,
            default => null,
        };
        if ($eventType === null) {
            return null;
        }

        $amount = WebhookParser::amountRubles($payload);
        $currency = WebhookParser::currency($payload);
        $currency = $currency !== '' ? $currency : null;
        $providerEventId = WebhookParser::providerEventId($payload);
        $providerEventId = $providerEventId !== '' ? $providerEventId : null;
        $includesMoney = $kind === WebhookParser::KIND_PAID || $kind === WebhookParser::KIND_AUTHORIZED;

        return $this->webhookEvent(
            $eventType,
            $externalId,
            WebhookParser::orderId($payload),
            WebhookParser::safePayload($payload),
            $providerEventId,
            amount: $includesMoney ? $amount : null,
            currency: $includesMoney || $kind === WebhookParser::KIND_REFUND ? $currency : null,
            refundAmount: $kind === WebhookParser::KIND_REFUND ? $amount : null,
        );
    }

    /**
     * PROVIDER: map MiniShop3 order fields onto the merchant create-payment body.
     *
     * @return array<string, mixed>
     */
    protected function buildPaymentRequest(msOrder $order, Settings $settings, PackageLogger $logger): array
    {
        $contacts = new CustomerContacts($this->modx);
        $payload = [
            'description' => mb_substr('Order #' . $order->get('num'), 0, 100),
            'amount' => Money::toKopecks((float) $order->get('cost')),
            'currency' => $settings->currency(),
            'return_url' => $this->getReturnUrl($order, true),
            'fail_url' => $this->getReturnUrl($order, false),
            'metadata' => [
                'order_id' => (int) $order->get('id'),
                'order_uuid' => (string) $order->get('uuid'),
            ],
        ];
        $email = $contacts->email($order);
        if ($email !== '') {
            $payload['email'] = $email;
        }
        $phone = $contacts->phone($order);
        if ($phone !== '') {
            $payload['phone'] = $phone;
        }
        if ($settings->sendReceipt()) {
            $receipt = (new ReceiptBuilder($this->modx, $settings, $logger))->build($order);
            if ($receipt !== []) {
                $payload['receipt'] = $receipt;
            }
        }

        return $payload;
    }

    protected function getReturnUrl(msOrder $order, bool $success = true): string
    {
        $thanksId = (int) $this->modx->getOption('ms3_order_redirect_thanks_id', null, 1);
        $params = ['msorder' => $order->get('uuid')];
        if (!$success) {
            $params['payment_fail'] = '1';
        }
        $url = (string) $this->modx->getOption('msp3paymentskeleton_success_url', null, '');
        if ($url !== '' && $success) {
            return $url;
        }
        $failUrl = (string) $this->modx->getOption('msp3paymentskeleton_fail_url', null, '');
        if ($failUrl !== '' && !$success) {
            return $failUrl;
        }

        return $this->modx->makeUrl($thanksId, '', $params, 'full');
    }

    protected function settings(): Settings
    {
        $method = $this->config['payment'] ?? null;

        return new Settings($this->modx, $method instanceof msPayment ? $method : null);
    }

    /**
     * PaymentService::recordAttemptFromSend() keeps payment_link only.
     * Sync/refund/capture need extra ids from create (operationId, mdOrder, invoice_id).
     *
     * @param array<string, mixed> $response
     */
    private function persistCreateExtras(msOrder $order, string $ref, string $link, array $response): void
    {
        $extra = [];
        foreach (['operation_id', 'operationId', 'invoice_id', 'mdOrder', 'provider_payment_id'] as $key) {
            $value = $response[$key] ?? '';
            if (is_scalar($value) && (string) $value !== '') {
                $extra[$key] = (string) $value;
            }
        }
        if ($extra === []) {
            return;
        }
        $method = $this->config['payment'] ?? null;
        if (!$method instanceof msPayment || !$this->modx->services->has('ms3_payment_lifecycle')) {
            return;
        }
        $lifecycle = $this->modx->services->get('ms3_payment_lifecycle');
        if (!$lifecycle instanceof PaymentLifecycleService) {
            return;
        }
        $lifecycle->initiate(
            (int) $order->get('id'),
            (int) $method->get('id'),
            static::class,
            (float) $order->get('cost'),
            $this->settings()->currency(),
            $ref,
            array_merge(['payment_link' => $link], $extra),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function webhookEvent(
        string $eventType,
        string $externalId,
        ?int $orderId,
        array $payload,
        ?string $providerEventId = null,
        ?float $amount = null,
        ?string $currency = null,
        ?float $refundAmount = null,
    ): PaymentWebhookEvent {
        return new PaymentWebhookEvent(
            eventType: $eventType,
            externalId: $externalId,
            orderId: $orderId,
            amount: $amount,
            currency: $currency,
            providerEventId: $providerEventId,
            refundAmount: $refundAmount,
            refundExternalId: $refundAmount !== null ? $providerEventId : null,
            payload: $payload,
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     */
    private function headerSignature(array $headers, array $payload): string
    {
        foreach (['X-Signature', 'X-Webhook-Signature', 'Signature'] as $name) {
            if (!empty($headers[$name])) {
                return (string) $headers[$name];
            }
        }

        return (string) ($payload['signature'] ?? '');
    }
}
