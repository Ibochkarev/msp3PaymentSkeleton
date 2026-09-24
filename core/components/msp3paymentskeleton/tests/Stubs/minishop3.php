<?php

declare(strict_types=1);

namespace MiniShop3\Controllers\Payment {
    use MiniShop3\Model\msOrder;
    use MiniShop3\Model\msPayment;

    interface PaymentProviderInterface
    {
        public function send(msOrder $order): array;
        public function receive(msOrder $order): array;
        public function getPaymentLink(msOrder $order): ?string;
        public function getCost(msOrder $order, msPayment $payment, float $cost): float;
        public function getOrderHash(msOrder $order): string;
    }

    interface PaymentWebhookHandlerInterface
    {
        public function verifyWebhook(string $rawBody, array $payload, array $headers, msPayment $method): bool;

        public function parseWebhook(array $payload, array $headers): ?PaymentWebhookEvent;
    }

    final class PaymentWebhookEvent
    {
        /**
         * @param array<string, mixed> $payload
         */
        public function __construct(
            public readonly string $eventType,
            public readonly ?string $externalId = null,
            public readonly ?int $orderId = null,
            public readonly ?string $orderUuid = null,
            public readonly ?float $amount = null,
            public readonly ?string $currency = null,
            public readonly ?string $providerEventId = null,
            public readonly ?float $refundAmount = null,
            public readonly ?string $refundExternalId = null,
            public readonly array $payload = [],
        ) {
        }
    }

    abstract class Payment
    {
        /** @var object */
        protected $ms3;
        /** @var object */
        protected $modx;
        /** @var array<string, mixed> */
        protected array $config = [];

        /**
         * @param array<string, mixed> $config
         */
        public function __construct(object $ms3, array $config = [])
        {
            $this->ms3 = $ms3;
            $this->modx = $ms3->modx ?? $ms3;
            $this->config = $config;
        }

        abstract public function send(msOrder $order): array;

        abstract public function receive(msOrder $order): array;

        /**
         * @param array<string, mixed> $data
         * @return array<string, mixed>
         */
        protected function error(string $message = '', array $data = [], array $placeholders = []): array
        {
            return ['success' => false, 'message' => $message, 'data' => $data];
        }

        /**
         * @param array<string, mixed> $data
         * @return array<string, mixed>
         */
        protected function success(string $message = '', array $data = [], array $placeholders = []): array
        {
            return ['success' => true, 'message' => $message, 'data' => $data];
        }

        protected function verifyWebhookHmac(string $rawBody, string $signature, ?string $secret = null): bool
        {
            return \MiniShop3\Services\Payment\PaymentWebhookHmac::verify($rawBody, $signature, $secret ?? '');
        }
    }
}

namespace MiniShop3\Services\Payment {
    final class PaymentAttemptStatus
    {
        public const PENDING = 'pending';
        public const AUTHORIZED = 'authorized';
        public const PAID = 'paid';
        public const FAILED = 'failed';
        public const CANCELLED = 'cancelled';
        public const REFUNDED = 'refunded';
        public const PARTIALLY_REFUNDED = 'partially_refunded';
    }

    final class PaymentWebhookHmac
    {
        public static function verify(string $rawBody, string $signature, string $secret): bool
        {
            if ($rawBody === '' || $signature === '' || $secret === '') {
                return false;
            }

            return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
        }
    }

    class PaymentLifecycleService
    {
    }

    class PaymentLifecycleException extends \RuntimeException
    {
        public const KIND_NOT_FOUND = 'not_found';
        public const KIND_CONFLICT = 'conflict';

        public function getKind(): string
        {
            return self::KIND_NOT_FOUND;
        }
    }
}

namespace MiniShop3\Model {
    class msOrder
    {
        /** @param array<string, mixed> $fields */
        public function __construct(private array $fields = [])
        {
        }

        public function get(string $key): mixed
        {
            return $this->fields[$key] ?? null;
        }

        public function getOne(string $alias): mixed
        {
            return $this->fields[$alias] ?? null;
        }

        public function getMany(string $alias): array
        {
            return $this->fields[$alias] ?? [];
        }
    }

    class msPayment
    {
        /** @param array<string, mixed> $fields */
        public function __construct(private array $fields = [])
        {
        }

        public function get(string $key): mixed
        {
            return $this->fields[$key] ?? null;
        }
    }

    class msOrderAddress
    {
    }

    class msCustomer
    {
    }
}

namespace MiniShop3\Utils {
    final class EventGate
    {
        /**
         * @param array<string, mixed> $properties
         * @return array{returnedValues: array<string, mixed>}
         */
        public static function invokeRaw(object $modx, string $eventName, array $properties): array
        {
            return ['returnedValues' => $properties];
        }
    }
}

namespace MiniShop3 {
    class MiniShop3
    {
    }
}

namespace MODX\Revolution {
    class modX
    {
        public function getOption(string $key, mixed $options = null, mixed $default = null): mixed
        {
            return $default;
        }

        public function log(int $level, string $message): void
        {
        }

        public function getObject(string $class, mixed $criteria = null): mixed
        {
            return null;
        }

        public function getCollection(string $class, mixed $criteria = null): array
        {
            return [];
        }

        public function makeUrl(int $id, string $context = '', array $params = [], string $scheme = ''): string
        {
            return 'https://example.test/' . $id;
        }
    }
}

namespace MODX\Revolution\Processors {
    abstract class Processor
    {
        /** @var object */
        protected $modx;
        /** @var array<string, mixed> */
        protected array $properties = [];

        /**
         * @param array<string, mixed> $properties
         */
        public function __construct(object $modx, array $properties = [])
        {
            $this->modx = $modx;
            $this->properties = $properties;
        }

        public function getProperty(string $key, mixed $default = null): mixed
        {
            return $this->properties[$key] ?? $default;
        }

        /**
         * @param array<string, mixed> $data
         * @return array<string, mixed>
         */
        public function failure(string $message = '', array $data = []): array
        {
            return ['success' => false, 'message' => $message, 'object' => $data];
        }

        /**
         * @param array<string, mixed> $data
         * @return array<string, mixed>
         */
        public function success(string $message = '', array $data = []): array
        {
            return ['success' => true, 'message' => $message, 'object' => $data];
        }
    }
}
