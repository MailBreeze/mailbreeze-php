<?php

declare(strict_types=1);

namespace MailBreeze\Exceptions;

class ServerException extends ApiException
{
    public function __construct(string $message, int $statusCode = 500, ?string $errorCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $errorCode, $previous);
    }
}
