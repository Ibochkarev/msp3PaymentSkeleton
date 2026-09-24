# msp3PaymentSkeleton

Образец платёжного дополнения для [MiniShop3](https://github.com/modx-pro/MiniShop3) на MODX 3 (ветка beta). Копируете репозиторий, запускаете `bin/init.php`, заполняете блоки `// PROVIDER:`.

Ядро ждёт класс `Payment` с `PaymentWebhookHandlerInterface` и сервис `ms3_payment_lifecycle`. Провайдер не пишет `status_id`. Статус заказа после оплаты, отказа и возврата задают настройки MiniShop3 (`ms3_status_paid`, `ms3_payment_on_failed_status`, `ms3_payment_on_refunded_status`).

- Версия пакета: 1.0.0-pl
- Лицензия: GPL v2 or later

## Содержание

1. [Быстрый старт](#быстрый-старт)
2. [Совместимость](#совместимость)
3. [Что править у провайдера](#что-править-у-провайдера)
4. [Контракт send()](#контракт-send)
5. [Настройка после установки](#настройка-после-установки)
6. [Настройки скелета](#настройки-скелета)
7. [Как это работает](#как-это-работает)
8. [Вкладка заказа](#вкладка-заказа)
9. [Чек 54-ФЗ](#чек-54-фз)
10. [Разбор сбоев](#разбор-сбоев)
11. [Сборка](#сборка)
12. [Документация](#документация)

## Быстрый старт

```bash
cp -R msp3PaymentSkeleton ../msp3MyPay
cd ../msp3MyPay
php bin/init.php --name=msp3MyPay --provider=MyPay
```

Опциональные модули оставляете флагом `--keep`:

```bash
php bin/init.php --name=msp3MyPay --provider=MyPay --keep=second-method,bindings,receipts-extra
```

`--no-encrypt` ставит `encrypt => false` в `_build/config.inc.php`. `--self-destruct` удаляет `bin/init.php` после успеха.

После `init.php` в README и лексиконах не должно остаться имени скелета.

## Совместимость

| Что | Версия |
|---|---|
| MODX Revolution | 3.0+ |
| MiniShop3 | >= 1.14.0-beta1 |
| PHP | 8.2+ |
| HTTP в образце | `file_get_contents`, не `curl_exec` |

## Что править у провайдера

Только файлы с меткой `// PROVIDER:`:

- `src/Api/ApiClient.php`: host, пути, тело запроса
- `src/Api/Signature.php`: алгоритм подписи, если не HMAC-SHA256
- `src/Api/WebhookParser.php`: события и поля payload
- `src/Payment/SkeletonPayment.php`: `buildPaymentRequest()`
- `src/Service/Settings.php`: ключи кабинета

Lifecycle, вкладку заказа, логи и сборку не переписывайте, пока провайдер не требует другого HTTP-формата.

## Контракт send()

`send()` обязан вернуть ключи `payment_link`, `payment_id`, `external_id`, `currency`. MiniShop3 пишет попытку в `ms3_payment_attempts` по `payment_id` / `external_id`.

Ядро в `recordAttemptFromSend()` копирует в payload только `payment_link`. Если процессорам нужен id провайдера (`operationId`, `mdOrder`, `invoice_id` сверх `external_id`), вызовите `lifecycle->initiate()` сами внутри `send()`.

Суммы считайте так, как требует API провайдера (рубли или копейки), и опишите это в README готового пакета.

## Настройка после установки

1. Секрет положите в `msPayment.properties`: `secret`, `secret_key` или `webhook_secret`. Системные настройки это запасной вариант, если properties пусты.
2. Webhook ядра, если кабинет шлёт JSON на URL способа:

```
https://САЙТ/assets/components/minishop3/api.php/api/v1/payment/webhook/{ID_СПОСОБА}
```

Пакетный `assets/components/msp3paymentskeleton/webhook.php` нужен, когда кабинет принимает один URL на магазин или шлёт form POST / не-JSON.

3. Тестовый режим по умолчанию включён.

`verifyWebhook` проверяет сырое тело. Заглушка `return true` в готовом пакете недопустима. В payload попытки не кладите `secret`, `token`, `api_key`, `password`.

Повтор того же `providerEventId` не должен ломать заказ.

## Настройки скелета

Ключ в MODX: `msp3paymentskeleton_<имя>`. После `init.php` префикс сменится на ваш пакет.

| Ключ | Тип | По умолчанию | Назначение |
|---|---|---|---|
| `msp3paymentskeleton_login` | text | пусто | Логин кабинета (замените) |
| `msp3paymentskeleton_secret_key` | password | пусто | Боевой секрет |
| `msp3paymentskeleton_test_secret_key` | password | пусто | Тестовый секрет |
| `msp3paymentskeleton_test_mode` | bool | да | Тестовый хост |
| `msp3paymentskeleton_payment_receipt` | bool | нет | Чек 54-ФЗ |
| `msp3paymentskeleton_vat_type` | list | `none` | НДС |
| `msp3paymentskeleton_payment_method` | list | `full_payment` | Признак расчёта |
| `msp3paymentskeleton_payment_object_delivery` | number | `4` | Предмет расчёта доставки |
| `msp3paymentskeleton_success_url` | text | пусто | Возврат после оплаты |
| `msp3paymentskeleton_fail_url` | text | пусто | Возврат после ошибки |
| `msp3paymentskeleton_debug` | bool | нет | Подробный лог |

## Как это работает

```
Покупатель     MiniShop3          ваш пакет             провайдер
    │               │                  │                       │
    │ оформление    │                  │                       │
    │──────────────>│ send()           │                       │
    │               │─────────────────>│ create payment        │
    │               │                  │──────────────────────>│
    │               │                  │  payment_link,        │
    │               │                  │  external_id          │
    │               │                  │<──────────────────────│
    │               │ redirect         │                       │
    │<──────────────│<─────────────────│                       │
    │          оплата у провайдера                             │
    │─────────────────────────────────────────────────────────>│
    │               │                  │  webhook              │
    │               │                  │<──────────────────────│
    │               │ lifecycle        │                       │
    │               │<─────────────────│                       │
```

Перед релизом пройдите [docs/CHECKLIST.md](docs/CHECKLIST.md).

## Вкладка заказа

Плагин вешает вкладку через `MS3OrderTabsRegistry`. Не занимайте ключи `info`, `products`, `address`, `history`, `ms3_shipment`. Handler берите через `PaymentService::loadPaymentHandler`.

Регистрация в `order-tab.js`: stub с `pendingTabs`, один `register()`, без `DOMContentLoaded` / `setTimeout`. Иначе после загрузки `order.min.js` ключ регистрируется дважды.

Возврат зовёт `lifecycle->refund()`, не `$order->set('status_id')`. На попытке `pending` или `authorized` вкладка не ходит в API и пишет, что сначала нужен webhook оплаты или capture.

`BaseProcessor::initialize()` сбрасывает общий `modError`. Иначе текст прошлого failure останется на следующем success.

## Чек 54-ФЗ

Чек не уходит без email покупателя. Если модуль receipts оставляли при `init.php`, правьте поля в событии пакета.

## Разбор сбоев

### Payment is not configured

Пустые login/secret. Скелет сам в шлюз не ходит, пока не заполните `ApiClient`.

### Webhook 400 Invalid JSON

Пакетный `webhook.php` ждёт то, что вы разобрали в `WebhookParser`. Form POST кабинета часто требует свой обработчик, не JSON-маршрут ядра.

### Процессоры без id провайдера

Ядро сохранило только `payment_link`. Образец уже вызывает `initiate()` в `send()`, если create вернул `operationId` / `invoice_id` / `mdOrder`. Допишите ключи провайдера в `persistCreateExtras()`.

### Возврат: дождитесь webhook

Попытка ещё `pending`. Сначала paid-событие или capture. Это правило MiniShop3, не дыра скелета.

## Сборка

```bash
cd core/components/msp3paymentskeleton && composer install && composer test
ENCRYPT=0 php _build/build.php
```

`encrypt => true` требует регистрацию пакета в [modstore.pro](https://modstore.pro/info/api).

## Документация

- [docs/MS3-INTEGRATION.md](docs/MS3-INTEGRATION.md): контракты MiniShop3
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md): поток оплаты
- [docs/PROVIDER-PORTING.md](docs/PROVIDER-PORTING.md): как перенести API
- [docs/CHECKLIST.md](docs/CHECKLIST.md): перед релизом
- [docs/changelog.txt](core/components/msp3paymentskeleton/docs/changelog.txt): история

## Лицензия

GPL v2 or later. См. [docs/license.txt](core/components/msp3paymentskeleton/docs/license.txt).
