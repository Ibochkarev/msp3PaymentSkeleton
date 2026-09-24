<?php

/**
 * Resolver: create msPayment records.
 */

use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

$modx = $transport->xpdo;

$payments = [
    [
        'name' => 'Оплата через Payment Skeleton',
        'description' => 'Шаблон оплаты MiniShop3. Замените на своего провайдера.',
        'class' => 'Msp3PaymentSkeleton\\Payment\\SkeletonPayment',
    ],
    // INIT:SECOND_METHOD
];

$classes = array_column($payments, 'class');

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    $modx->removeCollection('MiniShop3\Model\msPayment', [
        'class:IN' => $classes,
    ]);
    return true;
}

if ($options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_INSTALL
    && $options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_UPGRADE
) {
    return true;
}

if (!class_exists('MiniShop3\Model\msPayment')) {
    $modx->log(modX::LOG_LEVEL_WARN, '[msp3PaymentSkeleton] MiniShop3 not found, skipping msPayment creation');
    return true;
}

foreach ($payments as $row) {
    if ($modx->getCount('MiniShop3\Model\msPayment', ['class' => $row['class']])) {
        continue;
    }
    $payment = $modx->newObject('MiniShop3\Model\msPayment');
    $payment->fromArray([
        'name' => $row['name'],
        'description' => $row['description'],
        'price' => '',
        'logo' => '',
        'position' => 0,
        'active' => 1,
        'class' => $row['class'],
        'properties' => [
            'secret' => '',
            'secret_key' => '',
            'webhook_secret' => '',
            'test_secret' => '',
            'login' => '',
            'test_mode' => true,
        ],
    ], '', true, true);
    $payment->save();
    $modx->log(modX::LOG_LEVEL_INFO, '[msp3PaymentSkeleton] Created msPayment "' . $row['name'] . '"');
}

return true;
