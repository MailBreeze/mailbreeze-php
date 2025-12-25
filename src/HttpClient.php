<?php

declare(strict_types=1);

namespace MailBreeze;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use MailBreeze\Exceptions\ApiException;
use MailBreeze\Exceptions\AuthenticationException;
use MailBreeze\Exceptions\BadRequestException;
use MailBreeze\Exceptions\NotFoundException;
use MailBreeze\Exceptions\RateLimitException;
use MailBreeze\Exceptions\ServerException;
use MailBreeze\Exceptions\ValidationException;

class HttpClient
{
    private const DEFAULT_BASE_URL = 'https://api.mailbreeze.com/v1';
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_MAX_RETRIES = 3;
    private const DEFAULT_RETRY_DELAY = 1000; // milliseconds
    private const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];

    private ClientInterface $httpClient;
    private string $baseUrl;
    private int $maxRetries;
    private int $retryDelay;

    /**
     * @param array{
     *     http_client?: ClientInterface,
     *     base_url?: string,
     *     timeout?: int,
     *     max_retries?: int,
     *     retry_delay?: int,
     * } $options
     */
    public function __construct(
        private string $apiKey,
        array $options = []
    ) {
        $this->baseUrl = $options['base_url'] ?? self::DEFAULT_BASE_URL;
        $this->maxRetries = $options['max_retries'] ?? self::DEFAULT_MAX_RETRIES;
        $this->retryDelay = $options['retry_delay'] ?? self::DEFAULT_RETRY_DELAY;

        if (isset($options['http_client'])) {
            $this->httpClient = $options['http_client'];
        } else {
            $this->httpClient = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|null
     */
    public function get(string $path, array $query = []): ?array
    {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function post(string $path, ?array $body = null, array $options = []): ?array
    {
        return $this->request('POST', $path, $body, [], $options);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>|null
     */
    public function patch(string $path, ?array $body = null): ?array
    {
        return $this->request('PATCH', $path, $body);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function delete(string $path): ?array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    private function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
        array $options = []
    ): ?array {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'mailbreeze-php/1.0.0',
        ];

        // Handle idempotency key with header injection prevention
        if (isset($options['idempotency_key'])) {
            $idempotencyKey = $options['idempotency_key'];
            if (str_contains($idempotencyKey, "\r") || str_contains($idempotencyKey, "\n")) {
                throw new \InvalidArgumentException('Idempotency key contains invalid characters');
            }
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $requestOptions = [
            'headers' => $headers,
        ];

        if (!empty($query)) {
            $requestOptions['query'] = $query;
        }

        if ($body !== null) {
            $requestOptions['json'] = $body;
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = $this->httpClient->request($method, $this->baseUrl . $path, $requestOptions);
                $statusCode = $response->getStatusCode();
                $responseBody = (string) $response->getBody();

                if ($statusCode === 204 || $responseBody === '') {
                    return null;
                }

                $decoded = json_decode($responseBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ApiException('Invalid JSON response from API', $statusCode);
                }

                return $decoded;
            } catch (ConnectException $e) {
                $lastException = new ApiException('Connection failed: ' . $e->getMessage(), 0);
                if ($attempt < $this->maxRetries) {
                    $this->sleep($this->calculateDelay($attempt));
                    continue;
                }
                throw $lastException;
            } catch (RequestException $e) {
                $response = $e->getResponse();
                if ($response === null) {
                    throw new ApiException('Request failed: ' . $e->getMessage(), 0);
                }

                $statusCode = $response->getStatusCode();
                $responseBody = (string) $response->getBody();
                $data = json_decode($responseBody, true) ?? [];

                $message = $data['error'] ?? $e->getMessage();
                $errorCode = $data['code'] ?? null;

                // Handle retryable status codes
                if (in_array($statusCode, self::RETRYABLE_STATUS_CODES, true) && $statusCode !== 429) {
                    $lastException = $this->createException($statusCode, $message, $errorCode, $data, $response);
                    if ($attempt < $this->maxRetries) {
                        $this->sleep($this->calculateDelay($attempt));
                        continue;
                    }
                }

                throw $this->createException($statusCode, $message, $errorCode, $data, $response);
            }
        }

        throw $lastException ?? new ApiException('Request failed after retries', 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createException(
        int $statusCode,
        string $message,
        ?string $errorCode,
        array $data,
        mixed $response
    ): ApiException {
        return match ($statusCode) {
            400 => new BadRequestException($message, $errorCode),
            401 => new AuthenticationException($message, $errorCode),
            404 => new NotFoundException($message, $errorCode),
            422 => new ValidationException($message, $data['errors'] ?? [], $errorCode),
            429 => new RateLimitException($message, $this->parseRetryAfter($response), $errorCode),
            default => $statusCode >= 500
                ? new ServerException($message, $statusCode, $errorCode)
                : new ApiException($message, $statusCode, $errorCode),
        };
    }

    private function parseRetryAfter(mixed $response): ?int
    {
        if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
            return null;
        }

        $retryAfter = $response->getHeader('Retry-After')[0] ?? null;
        if ($retryAfter === null) {
            return null;
        }

        // Try to parse as integer (seconds)
        if (is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }

        // Try to parse as HTTP-date (RFC 1123)
        $timestamp = strtotime($retryAfter);
        if ($timestamp !== false) {
            $delta = $timestamp - time();

            return $delta > 0 ? $delta : null;
        }

        return null;
    }

    private function calculateDelay(int $attempt): int
    {
        // Exponential backoff: delay * 2^(attempt-1)
        return $this->retryDelay * (1 << ($attempt - 1));
    }

    private function sleep(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }

    /**
     * Prevent API key from appearing in var_dump() or print_r() output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => '[REDACTED]',
            'baseUrl' => $this->baseUrl,
            'maxRetries' => $this->maxRetries,
            'retryDelay' => $this->retryDelay,
        ];
    }
}
