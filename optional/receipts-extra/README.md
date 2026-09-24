# Чеки: список, полный расчёт, коррекция

Базовый чек 54-ФЗ уже в `ReceiptBuilder`. Этот модуль добавляет процессоры менеджера, если API провайдера умеет список чеков и коррекции.

## Подключение

```bash
php bin/init.php --name=msp3MyPay --provider=MyPay --keep=receipts-extra
```

Скрипт копирует процессоры и дописывает действия в `connector.php`. Кнопки вкладки добавьте в `order-tab.js` сами: `mgr/receipts`, `mgr/fullpayment`, `mgr/correction`.

## Удаление

Удалите три процессора и строки в `connector.php`.
