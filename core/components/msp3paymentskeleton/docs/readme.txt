msp3PaymentSkeleton — образец платёжного дополнения MiniShop3 (MODX 3.x, ветка beta).

Требования: MODX Revolution 3.x, MiniShop3 >= 1.14.0-beta1, PHP 8.2+.

Установка:
1. php bin/init.php --name=msp3MyPay --provider=MyPay
2. Соберите транспорт: ENCRYPT=0 php _build/build.php
3. Установите пакет. Появится способ оплаты с классом SkeletonPayment (после init — MyPayPayment).
4. Секрет положите в msPayment.properties (secret / secret_key / webhook_secret).
5. Webhook ядра:
   https://ВАШ_САЙТ/assets/components/minishop3/api.php/api/v1/payment/webhook/{ID_СПОСОБА}

Статус заказа меняет MiniShop3. Письма — Центр уведомлений MS3.

Документация: docs/MS3-INTEGRATION.md и README.md.
