<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;
use Msp3PaymentSkeleton\Exception\ProviderException;
use Msp3PaymentSkeleton\Service\AttemptReader;

class SyncProcessor extends BaseProcessor
{
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }
        $order = $this->requireOrder();
        if (!$order instanceof msOrder) {
            return $order;
        }
        $attempts = $this->attempts()->listForOrder((int) $order->get('id'));
        $settings = $this->settings($order);
        if (!$settings->isConfigured()) {
            return $this->success('', [
                'attempts' => $attempts,
                'note' => $this->modx->lexicon('msp3paymentskeleton.err_not_configured'),
            ]);
        }
        $paymentId = AttemptReader::providerRefundId(
            $this->latestAttempt((int) $order->get('id')) ?? [],
        );
        try {
            $response = $paymentId !== ''
                ? $settings->client()->getPayment($paymentId)
                : $settings->client()->listPayments();
        } catch (ProviderException $e) {
            $this->logger()->error('Sync failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }

        return $this->success('', $response);
    }
}
