<?php

$_lang['msp3paymentskeleton.tab_title'] = 'Payment Skeleton';
$_lang['msp3paymentskeleton.tab_attempts'] = 'Payment attempts';
$_lang['msp3paymentskeleton.tab_empty'] = 'No Payment Skeleton attempts for this order yet.';
$_lang['msp3paymentskeleton.action_refund'] = 'Refund';
$_lang['msp3paymentskeleton.action_cancel'] = 'Cancel payment';
$_lang['msp3paymentskeleton.action_sync'] = 'Sync';
$_lang['msp3paymentskeleton.field_amount'] = 'Refund amount';
$_lang['msp3paymentskeleton.field_reason'] = 'Reason';
$_lang['msp3paymentskeleton.confirm_refund'] = 'Send a refund request?';
$_lang['msp3paymentskeleton.confirm_cancel'] = 'Cancel this payment at the provider?';
$_lang['msp3paymentskeleton.err_order'] = 'Specify the order ID';
$_lang['msp3paymentskeleton.err_order_not_found'] = 'Order not found';
$_lang['msp3paymentskeleton.err_attempt'] = 'Payment attempt not found';
$_lang['msp3paymentskeleton.err_payment_id'] = 'Payment identifier is missing';
$_lang['msp3paymentskeleton.err_lifecycle'] = 'The provider accepted the request, but MiniShop3 did not update the attempt.';
$_lang['msp3paymentskeleton.err_refund_pending'] = 'Wait for a paid webhook or capture the hold first. Refund is allowed only after paid.';
$_lang['msp3paymentskeleton.err_refund_hold'] = 'Capture the hold first. Refund is allowed after capture.';
$_lang['msp3paymentskeleton.err_refund_done'] = 'This attempt is already refunded.';
$_lang['msp3paymentskeleton.err_refund_status'] = 'Refund is not available in the current attempt status.';
