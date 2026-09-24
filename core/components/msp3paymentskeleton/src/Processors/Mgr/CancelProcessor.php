<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;
use Msp3PaymentSkeleton\Exception\ProviderException;

class CancelProcessor extends BaseProcessor
{
    public function process()
    {
        if (!$this->checkWritePermissions()) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }
        $order = $this->requireOrder();
        if (!$order instanceof msOrder) {
            return $order;
        }
        $attempt = $this->latestAttempt((int) $order->get('id'));
        $paymentId = (string) ($attempt['external_id'] ?? '');
        if ($paymentId === '') {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_payment_id'));
        }
        try {
            $response = $this->settings($order)->client()->cancelPayment($paymentId);
        } catch (ProviderException $e) {
            $this->logger()->error('Cancel failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }

        return $this->success('', $response);
    }
}
