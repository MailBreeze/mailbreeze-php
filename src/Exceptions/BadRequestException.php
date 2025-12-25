<?php

declare(strict_types=1);

namespace MailBreeze\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $message, ?string $errorCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $errorCode, $previous);
    }
}
