# Перенос API провайдера

Эталон живого пакета на том же каноне: `Extras/msp3CDEKPay`.

## Порядок

1. `php bin/init.php --name=msp3Xxx --provider=Xxx`
2. В `ApiClient::HOST` и `prefix()` поставьте боевой и тестовый URL.
3. Замените пути `payments`, `refund`, `cancel` на эндпоинты кабинета.
4. В `Signature` оставьте HMAC, если провайдер так подписывает. Иначе скопируйте алгоритм из документации (CDEK: flatten + ksort + SHA256 upper).
5. В `WebhookParser` сопоставьте события кабинета статусам `paid` / `authorized` / `failed` / `cancelled` / `refunded`.
6. В `buildPaymentRequest()` соберите тело ордера: сумма в копейках или рублях, как требует API. Не перепутайте.
7. Если create отдаёт второй id (`operationId`, `mdOrder`), сохраните его в `persistCreateExtras()` / `initiate()`.
8. Если кабинет шлёт form POST без invoice_id, оставьте `WebhookParser::externalId()` пустым на этом поле. Обработчик подставит id из попытки заказа.
9. Прогоните `composer test`. Добавьте фикстуры из OpenAPI провайдера в `tests/Fixtures/`.
10. `ENCRYPT=0 php _build/build.php`, поставьте пакет, пропишите webhook.

## CDEK Pay как пример

| Задача | CDEK | Образец |
|---|---|---|
| Host | `https://secure.cdekfin.ru` | `ApiClient::HOST` |
| Тест | `test_merchant_api`, валюта `TST` | `prefix()` + `Settings::currency()` |
| Подпись | flatten, ksort, `\|`, SHA256 upper | `Signature` |
| Создание | `POST payment_orders` | `createPayment` |
| Webhook | объект `payment` + `signature` | `WebhookParser` + `verifyWebhook` |
| Один URL кабинета | `webhook.php` пакета | тот же файл |
| id vs access_key | разные поля | в образце одно `id` |

## Секреты

Сначала `msPayment.properties` (`secret`, `secret_key`, `webhook_secret`). Системная настройка — запас. Не кладите ключи в payload события.
