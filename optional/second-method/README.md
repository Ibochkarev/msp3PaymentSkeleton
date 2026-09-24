# Второй способ оплаты

Подключайте, если у провайдера два сценария (ссылка и QR, одностадийный и холд).

## Подключение

```bash
php bin/init.php --name=msp3MyPay --provider=MyPay --keep=second-method
```

Скрипт копирует `SkeletonSecondPayment.php` в `src/Payment/` и дописывает класс в `resolver_02_payment.php`.

## Удаление

Удалите `src/Payment/SkeletonSecondPayment.php` (после init — `{Provider}SecondPayment.php`) и вторую запись в резолвере `msPayment`.
