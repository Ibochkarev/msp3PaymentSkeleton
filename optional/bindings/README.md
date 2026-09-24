# Привязки карт и счетов

Нужны рекуррентные списания. Крона в образце нет: `BindingService::charge()` вызываете из своего кода и сами пишете попытку через `ms3_payment_service`.

## Подключение

```bash
php bin/init.php --name=msp3MyPay --provider=MyPay --keep=bindings
```

Скрипт копирует модель, сервис, сниппеты и заменяет `resolver_03_tables.php`.

## Удаление

Удалите `src/Model/`, `src/Service/BindingService.php`, сниппеты и верните пустой `resolver_03_tables.php`.
