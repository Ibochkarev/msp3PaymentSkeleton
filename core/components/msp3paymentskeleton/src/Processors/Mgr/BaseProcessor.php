<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Processors\Mgr;

use MiniShop3\Model\msOrder;
use MiniShop3\Services\Payment\PaymentLifecycleService;
use MODX\Revolution\Processors\Processor;
use Msp3PaymentSkeleton\Service\AttemptReader;
use Msp3PaymentSkeleton\Service\PackageLogger;
use Msp3PaymentSkeleton\Service\Settings;

abstract class BaseProcessor extends Processor
{
    public function checkPermissions(): bool
    {
        return $this->modx->hasPermission('msorder_save')
            || $this->modx->hasPermission('msorder_view')
            || $this->modx->hasPermission('edit_document');
    }

    public function initialize()
    {
        $this->modx->error->reset();

        return true;
    }

    protected function checkWritePermissions(): bool
    {
        return $this->modx->hasPermission('msorder_save')
            || $this->modx->hasPermission('edit_document');
    }

    protected function requireOrder(): msOrder|array
    {
        $orderId = (int) $this->getProperty('order_id');
        if ($orderId < 1) {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_order'));
        }
        $order = $this->modx->getObject(msOrder::class, $orderId);
        if (!$order) {
            return $this->failure($this->modx->lexicon('msp3paymentskeleton.err_order_not_found'));
        }

        return $order;
    }

    protected function settings(): Settings
    {
        return new Settings($this->modx);
    }

    protected function logger(): PackageLogger
    {
        return new PackageLogger($this->modx);
    }

    protected function attempts(): AttemptReader
    {
        return new AttemptReader($this->modx);
    }

    protected function lifecycle(): ?PaymentLifecycleService
    {
        if (!$this->modx->services->has('ms3_payment_lifecycle')) {
            return null;
        }
        $service = $this->modx->services->get('ms3_payment_lifecycle');

        return $service instanceof PaymentLifecycleService ? $service : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function latestAttempt(int $orderId): ?array
    {
        return $this->attempts()->latestForOrder($orderId);
    }
}
