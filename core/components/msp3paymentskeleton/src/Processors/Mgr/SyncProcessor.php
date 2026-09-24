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
        $paymentId = AttemptReader::providerRefundId(
            $this->latestAttempt((int) $order->get('id')) ?? [],
        );
        try {
            $response = $paymentId !== ''
                ? $this->settings()->client()->getPayment($paymentId)
                : $this->settings()->client()->listPayments();
        } catch (ProviderException $e) {
            $this->logger()->error('Sync failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }

        return $this->success('', $response);
    }
}
