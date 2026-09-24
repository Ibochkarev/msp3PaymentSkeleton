<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use Msp3PaymentSkeleton\Exception\ProviderException;

class CorrectionProcessor extends BaseProcessor
{
    public function process()
    {
        if (!$this->checkWritePermissions()) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }
        $receiptId = (string) $this->getProperty('receipt_id', '');
        if ($receiptId === '') {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_payment_id'));
        }
        try {
            $response = $this->settings()->client()->createPayment([
                'mode' => 'correction',
                'receipt_id' => $receiptId,
            ]);
        } catch (ProviderException $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success('', $response);
    }
}
