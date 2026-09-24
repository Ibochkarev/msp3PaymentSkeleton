<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Model\msOrder;
use MODX\Revolution\modX;

final class CustomerContacts
{
    public function __construct(private readonly modX $modx)
    {
    }

    public function email(msOrder $order): string
    {
        $address = $this->modx->getObject('MiniShop3\Model\msOrderAddress', ['order_id' => $order->get('id')]);
        if ($address) {
            $email = (string) $address->get('email');
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
        $linked = $order->getOne('Address');
        if ($linked) {
            $email = (string) $linked->get('email');
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return '';
    }

    public function phone(msOrder $order): string
    {
        $raw = '';
        $address = $this->modx->getObject('MiniShop3\Model\msOrderAddress', ['order_id' => $order->get('id')]);
        if ($address) {
            $raw = (string) $address->get('phone');
        }
        if ($raw === '') {
            $linked = $order->getOne('Address');
            if ($linked) {
                $raw = (string) $linked->get('phone');
            }
        }

        return self::normalizePhone($raw);
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if (preg_match('/^[78]\d{10}$/', $digits)) {
            return $digits;
        }

        return '';
    }
}
