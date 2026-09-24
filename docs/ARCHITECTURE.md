# Архитектура

```mermaid
sequenceDiagram
  participant Checkout
  participant PaymentService
  participant Handler as SkeletonPayment
  participant Provider
  participant Webhook as PaymentWebhookController
  participant Lifecycle as ms3_payment_lifecycle
  Checkout->>PaymentService: sendToPaymentGateway
  PaymentService->>Handler: send(order)
  Handler->>Provider: createPayment
  Provider-->>Handler: payment_link, id
  Handler-->>PaymentService: payment_link, payment_id, external_id, currency
  PaymentService->>Lifecycle: initiate attempt
  Provider->>Webhook: POST api/v1/payment/webhook/ID
  Webhook->>Handler: verifyWebhook rawBody
  Webhook->>Handler: parseWebhook
  Webhook->>Lifecycle: applyWebhook
  Lifecycle->>Lifecycle: status_id via ms3_status_paid
```

## Слои

| Слой | Кто пишет | Роль |
|---|---|---|
| `Api/*` | вы | HTTP, подпись, разбор webhook |
| `SkeletonPayment` | вы в `buildPaymentRequest` | контракт MS3 |
| `WebhookHandler` | образец | один URL кабинета |
| `ms3_payment_lifecycle` | ядро | попытки и статус заказа |
| Процессоры вкладки | образец | возврат, отмена, синк |

Handler создаёт `PaymentService::loadPaymentHandler`, не `new $class` снаружи ядра.

## Попытка

`send()` обязан вернуть `payment_link`, `payment_id`/`external_id`, `currency`. Иначе строка в `ms3_payment_attempts` не появится. В payload события нельзя класть ключи из `BLOCKED_PAYLOAD_KEYS` (`secret`, `token`, `api_key` и остальные). `WebhookParser::safePayload()` их срезает.
