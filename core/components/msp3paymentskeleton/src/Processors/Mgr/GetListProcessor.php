<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;

class GetListProcessor extends BaseProcessor
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

        return $this->success('', [
            'attempts' => $this->attempts()->listForOrder((int) $order->get('id')),
            'payment_link' => $this->attempts()->storedLink((int) $order->get('id')),
        ]);
    }
}
