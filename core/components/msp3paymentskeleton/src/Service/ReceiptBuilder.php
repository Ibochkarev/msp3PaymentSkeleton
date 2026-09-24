<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MiniShop3\Model\msOrder;
use MiniShop3\Utils\EventGate;
use MODX\Revolution\modX;
use Msp3PaymentSkeleton\Api\Money;

class ReceiptBuilder
{
    public const PREPARE_ITEM_EVENT = 'msp3PaymentSkeletonOnPrepareReceiptItem';

    public function __construct(
        private readonly modX $modx,
        private readonly Settings $settings,
        private readonly ?PackageLogger $logger = null,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(msOrder $order): array
    {
        $email = $this->getCustomerEmail($order);
        if ($email === '') {
            $this->debug('ReceiptBuilder: no customer email');
            return [];
        }

        $items = $this->buildItems($order);
        if ($items === []) {
            $this->debug('ReceiptBuilder: no receipt items');
            return [];
        }

        return self::alignSum($items, Money::toKopecks((float) $order->get('cost')));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function alignSum(array $items, int $expectedKopecks): array
    {
        $sum = 0;
        foreach ($items as $item) {
            $sum += (int) $item['sum'];
        }
        $diff = $expectedKopecks - $sum;
        if ($diff === 0 || $items === []) {
            return $items;
        }
        $last = count($items) - 1;
        $newSum = (int) $items[$last]['sum'] + $diff;
        if ($newSum > 0) {
            $items[$last]['sum'] = $newSum;
            $items[$last]['price'] = $newSum;
            $items[$last]['quantity'] = 1;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(msOrder $order): array
    {
        $items = [];
        $orderId = (int) $order->get('id');
        $orderProducts = $order->getMany('Products');
        if (!$orderProducts || count($orderProducts) === 0) {
            $orderProducts = $this->modx->getCollection('MiniShop3\Model\msOrderProduct', ['order_id' => $orderId]);
        }

        foreach ($orderProducts as $op) {
            $name = (string) $op->get('name');
            if ($name === '') {
                $name = 'Product';
            }
            $price = (float) $op->get('price');
            $count = (float) $op->get('count');
            if ($count < 0.001) {
                continue;
            }
            $priceKopecks = Money::toKopecks($price);
            $item = [
                'id' => 'p' . (string) $op->get('id'),
                'name' => mb_substr($name, 0, 128),
                'price' => $priceKopecks,
                'quantity' => round($count, 3),
                'sum' => Money::toKopecks($price * $count),
                'measure' => 0,
                'payment_object' => 1,
                'vat_type' => $this->settings->vatType(),
                'payment_method' => $this->settings->paymentMethod(),
            ];
            $item = $this->applyItemEvent($order, $op, $item);
            $items[] = $item;
        }

        $deliveryCost = (float) $order->get('delivery_cost');
        if ($deliveryCost > 0) {
            $deliveryKopecks = Money::toKopecks($deliveryCost);
            $items[] = [
                'id' => 'delivery-' . $orderId,
                'name' => 'Delivery',
                'price' => $deliveryKopecks,
                'quantity' => 1,
                'sum' => $deliveryKopecks,
                'measure' => 0,
                'payment_object' => $this->settings->deliveryPaymentObject(),
                'vat_type' => $this->settings->vatType(),
                'payment_method' => $this->settings->paymentMethod(),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function applyItemEvent(msOrder $order, object $orderProduct, array $item): array
    {
        $params = [
            'order' => $order,
            'orderProduct' => $orderProduct,
            'item' => $item,
        ];
        if (class_exists(EventGate::class)) {
            $gate = EventGate::invokeRaw($this->modx, self::PREPARE_ITEM_EVENT, $params);
            $returned = $gate['returnedValues'];
            if (isset($returned['item']) && is_array($returned['item'])) {
                return $returned['item'];
            }
        } else {
            $this->modx->invokeEvent(self::PREPARE_ITEM_EVENT, $params);
        }
        if (isset($params['item']) && is_array($params['item'])) {
            return $params['item'];
        }

        return $item;
    }

    private function getCustomerEmail(msOrder $order): string
    {
        $contacts = new CustomerContacts($this->modx);
        $email = $contacts->email($order);
        if ($email !== '') {
            return $email;
        }
        $props = $order->get('properties') ?: [];
        $fromProps = $props['email'] ?? '';
        if (is_string($fromProps) && filter_var($fromProps, FILTER_VALIDATE_EMAIL)) {
            return $fromProps;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function debug(string $message, array $context = []): void
    {
        $this->logger?->debug($message, $context);
    }
}
