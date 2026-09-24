<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Model\msOrder;
use MODX\Revolution\modX;
use Msp3PaymentSkeleton\Exception\ProviderException;
use Msp3PaymentSkeleton\Model\PaymentBinding;

class BindingService
{
    public function __construct(
        private readonly modX $modx,
        private readonly ?Settings $settings = null,
    ) {
    }

    /**
     * @return list<PaymentBinding>
     */
    public function listForUser(int $userId): array
    {
        $collection = $this->modx->getCollection(PaymentBinding::class, ['user_id' => $userId]);

        return array_values($collection);
    }

    /**
     * @return array<string, mixed>
     */
    public function startBinding(int $userId, string $successUrl, string $failUrl): array
    {
        $requestId = 'bind-' . bin2hex(random_bytes(8));
        $response = $this->settings()->client()->createPayment([
            'mode' => 'bind',
            'request_id' => $requestId,
            'user_id' => (string) $userId,
            'return_url' => $successUrl,
            'fail_url' => $failUrl,
        ]);
        $this->upsert([
            'user_id' => $userId,
            'kind' => PaymentBinding::KIND_CARD,
            'request_id' => $requestId,
            'state' => (string) ($response['state'] ?? 'created'),
            'payload' => $response,
        ]);

        return $response;
    }

    /**
     * Charge a stored binding. Record the attempt via PaymentService yourself.
     *
     * @return array<string, mixed>
     */
    public function charge(msOrder $order, string $requestId): array
    {
        $settings = $this->settings();

        return $settings->client()->createPayment([
            'mode' => 'recurrent',
            'request_id' => $requestId,
            'amount' => (int) round(((float) $order->get('cost')) * 100),
            'currency' => $settings->currency(),
            'description' => mb_substr('Order #' . $order->get('num'), 0, 100),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsert(array $data): PaymentBinding
    {
        $binding = !empty($data['request_id'])
            ? $this->modx->getObject(PaymentBinding::class, ['request_id' => $data['request_id']])
            : null;
        if (!$binding) {
            $binding = $this->modx->newObject(PaymentBinding::class);
            $binding->set('createdon', time());
        }
        foreach ($data as $key => $value) {
            $binding->set($key, $value);
        }
        $binding->set('updatedon', time());
        if (!$binding->save()) {
            throw new ProviderException('Could not save binding');
        }

        return $binding;
    }

    private function settings(): Settings
    {
        return $this->settings ?? new Settings($this->modx);
    }
}
