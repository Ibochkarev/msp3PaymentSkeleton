<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Controllers\Payment\PaymentWebhookHandlerInterface;
use MiniShop3\Model\msOrder;
use MiniShop3\Model\msPayment;
use MiniShop3\Services\Payment\PaymentLifecycleException;
use MiniShop3\Services\Payment\PaymentLifecycleService;
use MiniShop3\Utils\EventGate;
use MODX\Revolution\modX;
use Msp3PaymentSkeleton\Api\Signature;
use Msp3PaymentSkeleton\Api\WebhookParser;
/**
 * Package webhook for providers that allow only one cabinet URL.
 */
class WebhookHandler
{
    public const PROVIDER_EVENT = 'msp3PaymentSkeletonOnProviderEvent';

    public function __construct(
        private readonly modX $modx,
        private readonly ?PackageLogger $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     * @return array{ok: bool, http: int, message: string}
     */
    public function handle(string $rawBody, array $payload, array $headers = []): array
    {
        $logger = $this->logger ?? new PackageLogger($this->modx);
        $settings = new Settings($this->modx);
        $signature = $this->signatureFrom($payload, $headers);
        if ($signature === '' || !Signature::verify($rawBody, $settings->webhookSecret(), $signature)) {
            $logger->error('Webhook: invalid signature');
            return ['ok' => false, 'http' => 401, 'message' => 'Unauthorized'];
        }

        $payload = $this->withStoredExternalId($payload);

        $kind = WebhookParser::kind($payload);
        if ($kind === WebhookParser::KIND_UNKNOWN) {
            $this->fireProviderEvent($payload);
            $logger->debug('Webhook: unknown event acknowledged');
            return ['ok' => true, 'http' => 200, 'message' => 'ignored'];
        }

        $lifecycle = $this->lifecycle();
        if ($lifecycle === null) {
            $logger->error('Webhook: ms3_payment_lifecycle is not registered');
            return ['ok' => false, 'http' => 500, 'message' => 'Lifecycle unavailable'];
        }

        $methods = $this->methodsForPayload($payload);
        foreach ($methods as $method) {
            $handler = $this->loadHandler($method);
            if (!$handler instanceof PaymentWebhookHandlerInterface) {
                continue;
            }
            $event = $handler->parseWebhook($payload, $headers);
            if ($event === null) {
                continue;
            }
            $provider = (string) $method->get('class');
            try {
                $attempt = $lifecycle->applyWebhook($event, (int) $method->get('id'), $provider);
                $logger->debug('Webhook: applied', [
                    'attempt_id' => $attempt['id'] ?? null,
                    'status' => $attempt['status'] ?? null,
                ]);
                return ['ok' => true, 'http' => 200, 'message' => 'applied'];
            } catch (PaymentLifecycleException $e) {
                if ($e->getKind() === PaymentLifecycleException::KIND_NOT_FOUND) {
                    continue;
                }
                $logger->error('Webhook: lifecycle error', ['message' => $e->getMessage()]);
                return ['ok' => false, 'http' => 409, 'message' => $e->getMessage()];
            }
        }

        $logger->debug('Webhook: no matching payment attempt', [
            'external_id' => WebhookParser::externalId($payload),
        ]);

        return ['ok' => true, 'http' => 200, 'message' => 'unmatched'];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    private function signatureFrom(array $payload, array $headers): string
    {
        foreach (['X-Signature', 'X-Webhook-Signature', 'Signature'] as $header) {
            if (!empty($headers[$header])) {
                return (string) $headers[$header];
            }
        }

        return (string) ($payload['signature'] ?? '');
    }

    /**
     * Cabinet POST may omit the invoice_id that send() stored as external_id.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withStoredExternalId(array $payload): array
    {
        if (WebhookParser::externalId($payload) !== '') {
            return $payload;
        }
        $orderId = WebhookParser::orderId($payload);
        if ($orderId === null) {
            return $payload;
        }
        $attempt = (new AttemptReader($this->modx))->latestForOrder($orderId);
        $stored = (string) ($attempt['external_id'] ?? '');
        if ($stored !== '') {
            $payload['payment_id'] = $stored;
        }

        return $payload;
    }

    /**
     * Walk the order's method first so a second-method class does not 409.
     *
     * @param array<string, mixed> $payload
     * @return list<msPayment>
     */
    private function methodsForPayload(array $payload): array
    {
        $orderId = WebhookParser::orderId($payload);
        if ($orderId !== null) {
            $order = $this->modx->getObject(msOrder::class, $orderId);
            if ($order instanceof msOrder) {
                $method = $this->modx->getObject(msPayment::class, (int) $order->get('payment_id'));
                if (
                    $method instanceof msPayment
                    && (int) $method->get('active') === 1
                    && in_array((string) $method->get('class'), AttemptReader::providerClasses(), true)
                ) {
                    return [$method];
                }
            }
        }

        return array_values($this->modx->getCollection(msPayment::class, [
            'class:IN' => AttemptReader::providerClasses(),
            'active' => 1,
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function fireProviderEvent(array $payload): void
    {
        $params = ['payload' => $payload];
        if (class_exists(EventGate::class)) {
            EventGate::invokeRaw($this->modx, self::PROVIDER_EVENT, $params);
            return;
        }
        $this->modx->invokeEvent(self::PROVIDER_EVENT, $params);
    }

    private function lifecycle(): ?PaymentLifecycleService
    {
        if (!$this->modx->services->has('ms3_payment_lifecycle')) {
            return null;
        }
        $lifecycle = $this->modx->services->get('ms3_payment_lifecycle');

        return $lifecycle instanceof PaymentLifecycleService ? $lifecycle : null;
    }

    private function loadHandler(msPayment $method): ?PaymentWebhookHandlerInterface
    {
        if (!$this->modx->services->has('ms3_payment_service')) {
            return null;
        }
        $service = $this->modx->services->get('ms3_payment_service');
        if (!is_object($service) || !method_exists($service, 'loadPaymentHandler')) {
            return null;
        }
        $handler = $service->loadPaymentHandler($method);

        return $handler instanceof PaymentWebhookHandlerInterface ? $handler : null;
    }
}
