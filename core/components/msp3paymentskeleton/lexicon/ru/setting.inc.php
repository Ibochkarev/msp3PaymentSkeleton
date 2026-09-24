<?php

$_lang['setting_msp3paymentskeleton_login'] = 'Логин магазина';
$_lang['setting_msp3paymentskeleton_login_desc'] = 'Логин из кабинета провайдера. Секрет лучше хранить в msPayment.properties.';

$_lang['setting_msp3paymentskeleton_secret_key'] = 'Секретный ключ';
$_lang['setting_msp3paymentskeleton_secret_key_desc'] = 'Запасной ключ, если в properties способа нет secret / secret_key / webhook_secret.';

$_lang['setting_msp3paymentskeleton_test_secret_key'] = 'Тестовый секретный ключ';
$_lang['setting_msp3paymentskeleton_test_secret_key_desc'] = 'Используется при включённом тестовом режиме.';

$_lang['setting_msp3paymentskeleton_test_mode'] = 'Тестовый режим';
$_lang['setting_msp3paymentskeleton_test_mode_desc'] = 'Запросы идут на тестовый префикс API.';

$_lang['setting_msp3paymentskeleton_payment_receipt'] = 'Отправка чеков 54-ФЗ';
$_lang['setting_msp3paymentskeleton_payment_receipt_desc'] = 'Передавать позиции чека. Нужен email покупателя.';

$_lang['setting_msp3paymentskeleton_vat_type'] = 'Тип НДС';
$_lang['setting_msp3paymentskeleton_vat_type_desc'] = 'vat_type для позиций чека (none, vat0, vat10, vat20, …).';

$_lang['setting_msp3paymentskeleton_payment_method'] = 'Признак способа расчёта';
$_lang['setting_msp3paymentskeleton_payment_method_desc'] = 'payment_method для чека. По умолчанию full_payment.';

$_lang['setting_msp3paymentskeleton_payment_object_delivery'] = 'Признак предмета расчёта для доставки';
$_lang['setting_msp3paymentskeleton_payment_object_delivery_desc'] = 'Числовой payment_object для строки доставки. По умолчанию 4 (услуга).';

$_lang['setting_msp3paymentskeleton_success_url'] = 'URL успешной оплаты';
$_lang['setting_msp3paymentskeleton_success_url_desc'] = 'Пусто — страница благодарности MiniShop3.';

$_lang['setting_msp3paymentskeleton_fail_url'] = 'URL неуспешной оплаты';
$_lang['setting_msp3paymentskeleton_fail_url_desc'] = 'Пусто — страница благодарности с параметром payment_fail.';

$_lang['setting_msp3paymentskeleton_debug'] = 'Режим отладки';
$_lang['setting_msp3paymentskeleton_debug_desc'] = 'Писать подробности в лог MODX.';
