<?php

declare(strict_types=1);

namespace MailBreeze\Exceptions;

class ValidationException extends ApiException
{
    /**
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        string $message,
        protected array $errors = [],
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 422, $errorCode, $previous);
    }

    /**
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
