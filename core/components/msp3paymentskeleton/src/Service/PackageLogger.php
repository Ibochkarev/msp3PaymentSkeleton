<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Service;

use MODX\Revolution\modX;

class PackageLogger
{
    public function __construct(private readonly modX $modx)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        if (!(bool) $this->modx->getOption('msp3paymentskeleton_debug', null, false)) {
            return;
        }

        $this->modx->log(modX::LOG_LEVEL_DEBUG, $this->format($message, $context));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->modx->log(modX::LOG_LEVEL_ERROR, $this->format($message, $context));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function format(string $message, array $context): string
    {
        $line = '[msp3PaymentSkeleton] ' . $message;
        if ($context !== []) {
            $line .= ' | ' . json_encode(self::redact($context), JSON_UNESCAPED_UNICODE);
        }

        return $line;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $blocked = [
            'signature', 'secret', 'secret_key', 'password',
            'token', 'api_key', 'authorization',
        ];
        foreach ($blocked as $key) {
            if (array_key_exists($key, $context)) {
                $context[$key] = '[redacted]';
            }
        }
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
