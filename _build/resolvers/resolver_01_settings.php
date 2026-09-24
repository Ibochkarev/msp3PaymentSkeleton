<?php

/**
 * Resolver: create msp3paymentskeleton system settings and namespace.
 */

use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

$modx = $transport->xpdo;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    $modx->removeCollection('modSystemSetting', ['namespace' => 'msp3paymentskeleton']);
    return true;
}

if ($options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_INSTALL
    && $options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_UPGRADE
) {
    return true;
}

$ns = $modx->getObject('modNamespace', ['name' => 'msp3paymentskeleton']);
if (!$ns) {
    $ns = $modx->newObject('modNamespace');
    $ns->set('name', 'msp3paymentskeleton');
    $ns->set('path', '{core_path}components/msp3paymentskeleton/');
    $ns->set('assets_path', '{assets_path}components/msp3paymentskeleton/');
    $ns->save();
}

$settings = [
    ['key' => 'msp3paymentskeleton_login', 'value' => '', 'xtype' => 'textfield', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_secret_key', 'value' => '', 'xtype' => 'text-password', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_test_secret_key', 'value' => '', 'xtype' => 'text-password', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_test_mode', 'value' => true, 'xtype' => 'combo-boolean', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_payment_receipt', 'value' => false, 'xtype' => 'combo-boolean', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_vat_type', 'value' => 'none', 'xtype' => 'list', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_payment_method', 'value' => 'full_payment', 'xtype' => 'list', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_payment_object_delivery', 'value' => 4, 'xtype' => 'numberfield', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_success_url', 'value' => '', 'xtype' => 'textfield', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_fail_url', 'value' => '', 'xtype' => 'textfield', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
    ['key' => 'msp3paymentskeleton_debug', 'value' => false, 'xtype' => 'combo-boolean', 'namespace' => 'msp3paymentskeleton', 'area' => 'paymentskeleton'],
];

foreach ($settings as $def) {
    if ($modx->getObject('modSystemSetting', ['key' => $def['key']])) {
        continue;
    }
    $obj = $modx->newObject('modSystemSetting');
    $obj->fromArray($def, '', true, true);
    $obj->save();
}

foreach (['msp3paymentskeleton_secret_key', 'msp3paymentskeleton_test_secret_key'] as $secretKey) {
    $secret = $modx->getObject('modSystemSetting', ['key' => $secretKey]);
    if ($secret && $secret->get('xtype') === 'password') {
        $secret->set('xtype', 'text-password');
        $secret->save();
    }
}

return true;
