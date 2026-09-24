<?php

/**
 * Load msp3PaymentSkeleton autoload and register the MiniShop3 order tab.
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

$eventName = $modx->event->name ?? '';

$corePath = $modx->getOption('msp3paymentskeleton_core_path', null, $modx->getOption('core_path') . 'components/msp3paymentskeleton/');
if (file_exists($corePath . 'bootstrap.php')) {
    require_once $corePath . 'bootstrap.php';
}

if ($eventName !== 'msOnManagerCustomCssJs') {
    return;
}

$page = $scriptProperties['page'] ?? '';
if ($page !== 'order') {
    return;
}

$controller = $scriptProperties['controller'] ?? null;
if (!is_object($controller) || !method_exists($controller, 'addJavascript')) {
    return;
}

$assetsUrl = $modx->getOption(
    'msp3paymentskeleton_assets_url',
    null,
    $modx->getOption('assets_url') . 'components/msp3paymentskeleton/'
);
$modx->lexicon->load('msp3paymentskeleton:default');

$config = [
    'assetsUrl' => $assetsUrl,
    'connectorUrl' => $assetsUrl . 'connector.php',
    'lexicon' => [
        'tab_title' => $modx->lexicon('msp3paymentskeleton.tab_title'),
        'attempts' => $modx->lexicon('msp3paymentskeleton.tab_attempts'),
        'refund' => $modx->lexicon('msp3paymentskeleton.action_refund'),
        'cancel' => $modx->lexicon('msp3paymentskeleton.action_cancel'),
        'sync' => $modx->lexicon('msp3paymentskeleton.action_sync'),
        'amount' => $modx->lexicon('msp3paymentskeleton.field_amount'),
        'reason' => $modx->lexicon('msp3paymentskeleton.field_reason'),
        'empty' => $modx->lexicon('msp3paymentskeleton.tab_empty'),
        'confirm_refund' => $modx->lexicon('msp3paymentskeleton.confirm_refund'),
        'confirm_cancel' => $modx->lexicon('msp3paymentskeleton.confirm_cancel'),
    ],
];

$controller->addHtml(
    '<script>window.msp3PaymentSkeletonConfig = ' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>'
);
$controller->addCss($assetsUrl . 'css/mgr/order-tab.css');
$controller->addJavascript($assetsUrl . 'js/mgr/order-tab.js');
