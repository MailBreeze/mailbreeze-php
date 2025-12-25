<?php

declare(strict_types=1);

namespace MailBreeze\Exceptions;

class RateLimitException extends ApiException
{
    public function __construct(
        string $message,
        protected ?int $retryAfter = null,
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 429, $errorCode, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
