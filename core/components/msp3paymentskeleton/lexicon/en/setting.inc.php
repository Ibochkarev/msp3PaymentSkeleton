<?php

$_lang['setting_msp3paymentskeleton_login'] = 'Shop login';
$_lang['setting_msp3paymentskeleton_login_desc'] = 'Login from the provider cabinet. Prefer secrets in msPayment.properties.';

$_lang['setting_msp3paymentskeleton_secret_key'] = 'Secret key';
$_lang['setting_msp3paymentskeleton_secret_key_desc'] = 'Fallback if the payment method properties have no secret / secret_key / webhook_secret.';

$_lang['setting_msp3paymentskeleton_test_secret_key'] = 'Test secret key';
$_lang['setting_msp3paymentskeleton_test_secret_key_desc'] = 'Used when test mode is enabled.';

$_lang['setting_msp3paymentskeleton_test_mode'] = 'Test mode';
$_lang['setting_msp3paymentskeleton_test_mode_desc'] = 'Requests use the test API prefix.';

$_lang['setting_msp3paymentskeleton_payment_receipt'] = 'Send 54-FZ receipts';
$_lang['setting_msp3paymentskeleton_payment_receipt_desc'] = 'Attach receipt items. Customer email is required.';

$_lang['setting_msp3paymentskeleton_vat_type'] = 'VAT type';
$_lang['setting_msp3paymentskeleton_vat_type_desc'] = 'vat_type for receipt lines (none, vat0, vat10, vat20, …).';

$_lang['setting_msp3paymentskeleton_payment_method'] = 'Payment method flag';
$_lang['setting_msp3paymentskeleton_payment_method_desc'] = 'receipt payment_method. Default full_payment.';

$_lang['setting_msp3paymentskeleton_payment_object_delivery'] = 'Delivery payment object';
$_lang['setting_msp3paymentskeleton_payment_object_delivery_desc'] = 'Numeric payment_object for the delivery line. Default 4 (service).';

$_lang['setting_msp3paymentskeleton_success_url'] = 'Success return URL';
$_lang['setting_msp3paymentskeleton_success_url_desc'] = 'Empty uses the MiniShop3 thank-you page.';

$_lang['setting_msp3paymentskeleton_fail_url'] = 'Failure return URL';
$_lang['setting_msp3paymentskeleton_fail_url_desc'] = 'Empty uses the thank-you page with payment_fail.';

$_lang['setting_msp3paymentskeleton_debug'] = 'Debug mode';
$_lang['setting_msp3paymentskeleton_debug_desc'] = 'Write details to the MODX log.';
