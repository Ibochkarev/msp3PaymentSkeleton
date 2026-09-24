<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Exception;

class ProviderException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly string $errorCode = '',
        int $httpStatus = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->getCode();
    }
}
