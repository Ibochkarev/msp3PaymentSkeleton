<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Payment;

use MiniShop3\Model\msOrder;
use Msp3PaymentSkeleton\Service\PackageLogger;
use Msp3PaymentSkeleton\Service\Settings;

/**
 * Second checkout path (QR, hold, SBP). Override send() only.
 */
class SkeletonSecondPayment extends SkeletonPayment
{
    public function send(msOrder $order): array
    {
        $settings = $this->settings();
        $logger = new PackageLogger($this->modx);
        if ($settings->login() === '' || $settings->secret() === '') {
            return $this->error('Payment is not configured');
        }

        $request = $this->buildPaymentRequest($order, $settings, $logger);
        $request['method'] = 'second';
        $logger->debug('createPayment second method', ['payload' => $request]);

        try {
            $response = $settings->client()->createPayment($request);
        } catch (\Throwable $e) {
            $logger->error('createPayment (second) failed: ' . $e->getMessage());
            return $this->error($e->getMessage());
        }

        $link = (string) ($response['confirmation_url'] ?? $response['payment_link'] ?? $response['qr_link'] ?? '');
        $paymentId = (string) ($response['id'] ?? $response['payment_id'] ?? '');
        if ($link === '' || $paymentId === '') {
            return $this->error('Payment URL not received');
        }

        return $this->success('', [
            'redirect' => $link,
            'payment_link' => $link,
            'payment_id' => $paymentId,
            'external_id' => $paymentId,
            'currency' => $settings->currency(),
            'order_id' => (int) $order->get('id'),
            'order_num' => (string) $order->get('num'),
            'msorder' => (string) $order->get('uuid'),
        ]);
    }
}
