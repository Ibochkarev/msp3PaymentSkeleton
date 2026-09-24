# MiniShop3: что образец закрывает

Источник: ветка `beta` MiniShop3.

## Payment

Класс наследует `MiniShop3\Controllers\Payment\Payment` и реализует `PaymentWebhookHandlerInterface`.

`send()` возвращает `$this->success('', [...])`. Обязательные ключи: `payment_link`, `payment_id`, `external_id`, `currency`.

`PaymentService::recordAttemptFromSend()` копирует в payload только `payment_link`. Id провайдера сверх `external_id` (`operationId`, `mdOrder`, `invoice_id`) пишите сами через `lifecycle->initiate()` в `send()`, иначе sync/refund/capture их не увидят.

Возврат из вкладки разрешён только с попытки `paid` или `partially_refunded`. На `pending` не вызывайте API провайдера. MiniShop3 ответит `ms3_err_payment_event_conflict`.

Базовый класс уже даёт:

- `getCost()` — комиссия способа, база только корзина (#460)
- `getPaymentLink()` — через `PaymentService::resolvePaymentLink` (сохранённая открытая попытка, иначе `send()` + `initiate`)
- `getOrderHash()` — HMAC по id/num/cost/createdon и `ms3_payment_secret`
- `verifyWebhookHmac()` / `webhookSecret()` — сначала `msPayment.properties` (`secret`, `secret_key`, `webhook_secret`), потом `ms3_payment_secret`

`receive()` в каноне beta не пишет статус. Его вызывает старый callback, не ядровой webhook.

## Webhook

Основной путь:

```
POST /assets/components/minishop3/api.php/api/v1/payment/webhook/{payment_method_id}
```

`PaymentWebhookController` читает raw body, вызывает `verifyWebhook`, затем `parseWebhook`, затем `ms3_payment_lifecycle->applyWebhook()`.

`verifyWebhook` получает байты тела. Для HMAC используйте `$this->verifyWebhookHmac($rawBody, $signature, $secret)`. Заглушка `return true` пускает подделку до `resolveAttempt()`.

`parseWebhook` возвращает `PaymentWebhookEvent`. Поле `eventType` — константа `PaymentAttemptStatus`: `pending`, `authorized`, `paid`, `failed`, `cancelled`, `refunded`, `partially_refunded`.

Пакетный `webhook.php` нужен, если кабинет даёт один URL. Он ищет попытку по `external_id` среди активных способов пакета. Неизвестный id отвечает `200`, чтобы провайдер не долбил повтор.

## Lifecycle

Допустимые переходы:

- pending → pending, authorized, paid, failed, cancelled
- authorized → authorized, paid, failed, cancelled
- paid → paid, refunded, partially_refunded
- partially_refunded → partially_refunded, refunded
- failed / cancelled / refunded — финал

Идемпотентность держит `providerEventId`. Возврат из вкладки зовёт `PaymentLifecycleService::refund()`, не `status_id`.

Ошибки: `PaymentLifecycleException` с kind `conflict`, `not_found` или validation. Ядро мапит их в HTTP 409 / 404 / 400.

## Статусы заказа и письма

| Настройка | Роль |
|---|---|
| `ms3_status_paid` | после paid (обычно 3) |
| `ms3_payment_on_failed_status` | после failed, 0 = не менять |
| `ms3_payment_on_refunded_status` | после полного возврата (обычно 5) |
| `ms3_payment_link_statuses` | для каких статусов резолвить ссылку |
| `ms3_status_new` | запасной статус для ссылки |
| `ms3_order_redirect_thanks_id` | страница возврата |

`payment_link` попадает в письма о заказе и смене статуса (#458). Письма шлёт Центр уведомлений MS3.

Пара доставка/оплата проверяется на submit (#459). На витрину уходят только поля `PaymentPublicFields`: id, name, description, price, logo. Класс и properties туда не входят.

## Менеджер

Плагин на `msOnManagerCustomCssJs` при `page=order` регистрирует вкладку через `MS3OrderTabsRegistry`. Ключ вкладки не должен совпадать с `info`, `products`, `address`, `ms3_shipment`, `history`. Тип `vue` или `extjs`. Connector проверяет сессию mgr и права `msorder_save` / `msorder_view`.

## События

`EventGate::invokeRaw`, если класс есть. Иначе `invokeEvent`.

- `msp3PaymentSkeletonOnPrepareReceiptItem` — позиция чека (`item`, `order`, `orderProduct`)
- `msp3PaymentSkeletonOnProviderEvent` — неизвестное событие провайдера (ядро статус не меняет)

## Сборка

`EncryptedVehicle` + `resolve.encryption.php`. Локально `ENCRYPT=0`. Резолверы покрывают install / upgrade / uninstall. `msPayment.properties` создаётся с пустыми ключами секрета.
