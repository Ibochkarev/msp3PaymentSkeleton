<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Model\msPayment;
use MODX\Revolution\modX;
use Msp3PaymentSkeleton\Api\ApiClient;

/**
 * PROVIDER: add keys the merchant API needs. Prefer msPayment.properties over system settings.
 */
final class Settings
{
    public function __construct(
        private readonly modX $modx,
        private readonly ?msPayment $method = null,
    ) {
    }

    public function withMethod(?msPayment $method): self
    {
        return new self($this->modx, $method);
    }

    public function login(): string
    {
        return $this->fromProperties('login')
            ?: (string) $this->modx->getOption('msp3paymentskeleton_login', null, '');
    }

    public function isTestMode(): bool
    {
        $fromProps = $this->fromProperties('test_mode');
        if ($fromProps !== '') {
            return in_array(strtolower($fromProps), ['1', 'true', 'yes'], true);
        }

        return (bool) $this->modx->getOption('msp3paymentskeleton_test_mode', null, true);
    }

    public function secret(): string
    {
        if ($this->isTestMode()) {
            $test = $this->fromProperties('test_secret_key')
                ?: $this->fromProperties('test_secret')
                ?: (string) $this->modx->getOption('msp3paymentskeleton_test_secret_key', null, '');
            if ($test !== '') {
                return $test;
            }
        }

        return $this->secretFromMethodOrSetting();
    }

    public function webhookSecret(): string
    {
        $fromProps = $this->fromProperties('webhook_secret')
            ?: $this->fromProperties('secret')
            ?: $this->fromProperties('secret_key');
        if ($fromProps !== '') {
            return $fromProps;
        }

        return $this->secret();
    }

    public function currency(): string
    {
        $fromProps = $this->fromProperties('currency');
        if ($fromProps !== '') {
            return $fromProps;
        }

        return $this->isTestMode() ? 'TST' : 'RUB';
    }

    public function sendReceipt(): bool
    {
        return (bool) $this->modx->getOption('msp3paymentskeleton_payment_receipt', null, false);
    }

    public function vatType(): string
    {
        $value = (string) $this->modx->getOption('msp3paymentskeleton_vat_type', null, 'none');

        return $value !== '' ? $value : 'none';
    }

    public function paymentMethod(): string
    {
        $value = (string) $this->modx->getOption('msp3paymentskeleton_payment_method', null, 'full_payment');

        return $value !== '' ? $value : 'full_payment';
    }

    public function deliveryPaymentObject(): int
    {
        return (int) $this->modx->getOption('msp3paymentskeleton_payment_object_delivery', null, 4) ?: 4;
    }

    public function client(): ApiClient
    {
        return new ApiClient($this->login(), $this->secret(), $this->isTestMode());
    }

    private function secretFromMethodOrSetting(): string
    {
        $fromProps = $this->fromProperties('secret')
            ?: $this->fromProperties('secret_key')
            ?: $this->fromProperties('webhook_secret');
        if ($fromProps !== '') {
            return $fromProps;
        }

        return (string) $this->modx->getOption('msp3paymentskeleton_secret_key', null, '');
    }

    private function fromProperties(string $key): string
    {
        if (!$this->method instanceof msPayment) {
            return '';
        }
        $props = $this->method->get('properties');
        if (is_string($props) && $props !== '') {
            $decoded = json_decode($props, true);
            $props = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($props)) {
            return '';
        }
        $value = $props[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
