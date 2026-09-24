<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;
use Msp3PaymentSkeleton\Exception\ProviderException;

class ReceiptsProcessor extends BaseProcessor
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
        $attempt = $this->latestAttempt((int) $order->get('id'));
        $paymentId = (string) ($attempt['external_id'] ?? '');
        try {
            $response = $paymentId !== ''
                ? $this->settings()->client()->getPayment($paymentId)
                : $this->settings()->client()->listPayments();
        } catch (ProviderException $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success('', $response);
    }
}
