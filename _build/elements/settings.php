<?php

/**
 * System settings for msp3PaymentSkeleton.
 * Key in MODX: msp3paymentskeleton_<key> (namespace: msp3paymentskeleton).
 */

return [
    'login' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_login',
        'description' => 'setting_msp3paymentskeleton_login_desc',
    ],
    'secret_key' => [
        'xtype' => 'text-password',
        'value' => '',
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_secret_key',
        'description' => 'setting_msp3paymentskeleton_secret_key_desc',
    ],
    'test_secret_key' => [
        'xtype' => 'text-password',
        'value' => '',
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_test_secret_key',
        'description' => 'setting_msp3paymentskeleton_test_secret_key_desc',
    ],
    'test_mode' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_test_mode',
        'description' => 'setting_msp3paymentskeleton_test_mode_desc',
    ],
    'payment_receipt' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_payment_receipt',
        'description' => 'setting_msp3paymentskeleton_payment_receipt_desc',
    ],
    'vat_type' => [
        'xtype' => 'list',
        'value' => 'none',
        'area' => 'paymentskeleton',
        'options' => 'none==Без НДС,vat0==НДС 0%,vat5==НДС 5%,vat7==НДС 7%,vat10==НДС 10%,vat20==НДС 20%,vat22==НДС 22%,vat105==НДС 5/105,vat107==НДС 7/107,vat110==НДС 10/110,vat120==НДС 20/120,vat122==НДС 22/122',
        'name' => 'setting_msp3paymentskeleton_vat_type',
        'description' => 'setting_msp3paymentskeleton_vat_type_desc',
    ],
    'payment_method' => [
        'xtype' => 'list',
        'value' => 'full_payment',
        'area' => 'paymentskeleton',
        'options' => 'full_payment==Полный расчёт,full_prepayment==Предоплата 100%,partial_payment==Частичный расчёт и кредит,credit==Передача в кредит,credit_payment==Оплата кредита',
        'name' => 'setting_msp3paymentskeleton_payment_method',
        'description' => 'setting_msp3paymentskeleton_payment_method_desc',
    ],
    'payment_object_delivery' => [
        'xtype' => 'numberfield',
        'value' => 4,
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_payment_object_delivery',
        'description' => 'setting_msp3paymentskeleton_payment_object_delivery_desc',
    ],
    'success_url' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_success_url',
        'description' => 'setting_msp3paymentskeleton_success_url_desc',
    ],
    'fail_url' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_fail_url',
        'description' => 'setting_msp3paymentskeleton_fail_url_desc',
    ],
    'debug' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'paymentskeleton',
        'name' => 'setting_msp3paymentskeleton_debug',
        'description' => 'setting_msp3paymentskeleton_debug_desc',
    ],
];
