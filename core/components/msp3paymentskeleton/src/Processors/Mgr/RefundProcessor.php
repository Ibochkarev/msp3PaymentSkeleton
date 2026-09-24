<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;
use MiniShop3\Services\Payment\PaymentAttemptStatus;
use MiniShop3\Services\Payment\PaymentLifecycleException;
use Msp3PaymentSkeleton\Api\Money;
use Msp3PaymentSkeleton\Exception\ProviderException;
use Msp3PaymentSkeleton\Service\AttemptReader;

class RefundProcessor extends BaseProcessor
{
    /** @var list<string> */
    private const REFUNDABLE_STATUSES = [
        PaymentAttemptStatus::PAID,
        PaymentAttemptStatus::PARTIALLY_REFUNDED,
    ];

    /** @var array<string, string> */
    private const REFUND_BLOCK_LEXICON = [
        PaymentAttemptStatus::PENDING => 'msp3paymentskeleton.err_refund_pending',
        PaymentAttemptStatus::AUTHORIZED => 'msp3paymentskeleton.err_refund_hold',
        PaymentAttemptStatus::REFUNDED => 'msp3paymentskeleton.err_refund_done',
    ];

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
        if ($attempt === null) {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_attempt'));
        }
        $paymentId = AttemptReader::providerRefundId($attempt)
            ?: (string) $this->getProperty('payment_id', '');
        if ($paymentId === '') {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_payment_id'));
        }
        $amount = (float) $this->getProperty('amount', $order->get('cost'));
        $amount = $amount > 0 ? $amount : (float) $order->get('cost');
        $lexiconKey = self::refundBlockLexicon((string) ($attempt['status'] ?? ''));
        if ($lexiconKey !== null) {
            return $this->failure($this->modx->lexicon($lexiconKey));
        }
        $reason = (string) $this->getProperty('reason', 'Refund');
        try {
            $response = $this->settings()->client()->refundPayment($paymentId, [
                'amount' => Money::toKopecks($amount),
                'reason' => $reason,
            ]);
        } catch (ProviderException $e) {
            $this->logger()->error('Refund failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }

        $lifecycle = $this->lifecycle();
        if ($lifecycle !== null) {
            try {
                $lifecycle->refund((int) $attempt['id'], $amount, (string) ($response['id'] ?? $paymentId));
            } catch (PaymentLifecycleException $e) {
                $this->logger()->error('Refund recorded at provider but lifecycle failed', [
                    'error' => $e->getMessage(),
                ]);
                return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_lifecycle') . ' ' . $e->getMessage());
            }
        }

        return $this->success('', $response);
    }

    /**
     * MiniShop3 allows refund only from paid / partially_refunded.
     * Refuse earlier so the tab does not call the provider and then get a generic conflict.
     */
    public static function refundBlockLexicon(string $status): ?string
    {
        if (in_array($status, self::REFUNDABLE_STATUSES, true)) {
            return null;
        }

        return self::REFUND_BLOCK_LEXICON[$status] ?? 'msp3paymentskeleton.err_refund_status';
    }
}
