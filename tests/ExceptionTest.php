<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use MailBreeze\Exceptions\ApiException;
use MailBreeze\Exceptions\AuthenticationException;
use MailBreeze\Exceptions\BadRequestException;
use MailBreeze\Exceptions\NotFoundException;
use MailBreeze\Exceptions\RateLimitException;
use MailBreeze\Exceptions\ServerException;
use MailBreeze\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testApiExceptionCreation(): void
    {
        $exception = new ApiException('Something went wrong', 500, 'ERR_001');

        $this->assertEquals('Something went wrong', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('ERR_001', $exception->getErrorCode());
    }

    public function testApiExceptionWithoutErrorCode(): void
    {
        $exception = new ApiException('Error', 400);

        $this->assertNull($exception->getErrorCode());
    }

    public function testAuthenticationException(): void
    {
        $exception = new AuthenticationException('Invalid API key');

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testBadRequestException(): void
    {
        $exception = new BadRequestException('Bad request');

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testNotFoundException(): void
    {
        $exception = new NotFoundException('Resource not found');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testRateLimitException(): void
    {
        $exception = new RateLimitException('Too many requests', 30);

        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertEquals(30, $exception->getRetryAfter());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testRateLimitExceptionDefaultRetryAfter(): void
    {
        $exception = new RateLimitException('Too many requests');

        $this->assertNull($exception->getRetryAfter());
    }

    public function testServerException(): void
    {
        $exception = new ServerException('Internal server error', 503);

        $this->assertEquals(503, $exception->getStatusCode());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testValidationException(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
            'subject' => ['The subject field must be a string.'],
        ];
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testValidationExceptionEmptyErrors(): void
    {
        $exception = new ValidationException('Validation failed');

        $this->assertEquals([], $exception->getErrors());
    }
}
