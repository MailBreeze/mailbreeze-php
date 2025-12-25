<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MailBreeze\Exceptions\ApiException;
use MailBreeze\Exceptions\AuthenticationException;
use MailBreeze\Exceptions\BadRequestException;
use MailBreeze\Exceptions\NotFoundException;
use MailBreeze\Exceptions\RateLimitException;
use MailBreeze\Exceptions\ServerException;
use MailBreeze\Exceptions\ValidationException;
use MailBreeze\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    private function createMockClient(array $responses): HttpClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        return new HttpClient('test_api_key', [
            'http_client' => $guzzle,
            'max_retries' => 3,
            'retry_delay' => 0, // No delay for tests
        ]);
    }

    public function testSuccessfulGetRequest(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['id' => '123', 'name' => 'Test'])),
        ]);

        $result = $client->get('/test');

        $this->assertEquals(['id' => '123', 'name' => 'Test'], $result);
    }

    public function testSuccessfulPostRequest(): void
    {
        $client = $this->createMockClient([
            new Response(201, [], json_encode(['id' => '456'])),
        ]);

        $result = $client->post('/test', ['name' => 'Test']);

        $this->assertEquals(['id' => '456'], $result);
    }

    public function testSuccessfulPatchRequest(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['updated' => true])),
        ]);

        $result = $client->patch('/test/123', ['name' => 'Updated']);

        $this->assertEquals(['updated' => true], $result);
    }

    public function testSuccessfulDeleteRequest(): void
    {
        $client = $this->createMockClient([
            new Response(204, [], ''),
        ]);

        $result = $client->delete('/test/123');

        $this->assertNull($result);
    }

    public function testQueryParameters(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['items' => []])),
        ]);

        $result = $client->get('/test', ['page' => 1, 'limit' => 10]);

        $this->assertEquals(['items' => []], $result);
    }

    public function testAuthenticationError(): void
    {
        $client = $this->createMockClient([
            new Response(401, [], json_encode(['error' => 'Invalid API key'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->get('/test');
    }

    public function testBadRequestError(): void
    {
        $client = $this->createMockClient([
            new Response(400, [], json_encode(['error' => 'Bad request'])),
        ]);

        $this->expectException(BadRequestException::class);
        $client->post('/test', []);
    }

    public function testNotFoundError(): void
    {
        $client = $this->createMockClient([
            new Response(404, [], json_encode(['error' => 'Not found'])),
        ]);

        $this->expectException(NotFoundException::class);
        $client->get('/test/nonexistent');
    }

    public function testValidationError(): void
    {
        $client = $this->createMockClient([
            new Response(422, [], json_encode([
                'error' => 'Validation failed',
                'errors' => ['email' => ['Required']],
            ])),
        ]);

        $this->expectException(ValidationException::class);
        $client->post('/test', []);
    }

    public function testRateLimitError(): void
    {
        $client = $this->createMockClient([
            new Response(429, ['Retry-After' => '30'], json_encode(['error' => 'Rate limited'])),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(30, $e->getRetryAfter());
        }
    }

    public function testServerError(): void
    {
        // 3 failures then give up
        $client = $this->createMockClient([
            new Response(500, [], json_encode(['error' => 'Server error'])),
            new Response(500, [], json_encode(['error' => 'Server error'])),
            new Response(500, [], json_encode(['error' => 'Server error'])),
        ]);

        $this->expectException(ServerException::class);
        $client->get('/test');
    }

    public function testRetryOnServerError(): void
    {
        // Fail twice, succeed on third
        $client = $this->createMockClient([
            new Response(500, [], json_encode(['error' => 'Server error'])),
            new Response(502, [], json_encode(['error' => 'Bad gateway'])),
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $result = $client->get('/test');

        $this->assertEquals(['success' => true], $result);
    }

    public function testRetryOnConnectionError(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', '/test')),
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('test_api_key', [
            'http_client' => $guzzle,
            'max_retries' => 3,
            'retry_delay' => 0,
        ]);

        $result = $client->get('/test');

        $this->assertEquals(['success' => true], $result);
    }

    public function testNoRetryOnNonRetryableStatus(): void
    {
        $client = $this->createMockClient([
            new Response(400, [], json_encode(['error' => 'Bad request'])),
        ]);

        $this->expectException(BadRequestException::class);
        $client->post('/test', []);
    }

    public function testIdempotencyKey(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['id' => '123'])),
        ]);

        $result = $client->post('/test', ['data' => 'test'], [
            'idempotency_key' => 'unique-key-123',
        ]);

        $this->assertEquals(['id' => '123'], $result);
    }

    public function testIdempotencyKeyHeaderInjectionPrevention(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['id' => '123'])),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $client->post('/test', ['data' => 'test'], [
            'idempotency_key' => "key\r\nX-Injected: value",
        ]);
    }

    public function testCustomTimeout(): void
    {
        $client = new HttpClient('test_api_key', [
            'timeout' => 60,
        ]);

        // Just verify the client is created - actual timeout is tested via Guzzle
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testCustomBaseUrl(): void
    {
        $client = new HttpClient('test_api_key', [
            'base_url' => 'https://custom.api.com',
        ]);

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testEmptyResponseBody(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], ''),
        ]);

        $result = $client->get('/test');

        $this->assertNull($result);
    }

    public function testMalformedJsonResponse(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], 'not json'),
        ]);

        $this->expectException(ApiException::class);
        $client->get('/test');
    }

    public function testErrorResponseWithCode(): void
    {
        $client = $this->createMockClient([
            new Response(400, [], json_encode([
                'error' => 'Invalid input',
                'code' => 'INVALID_INPUT',
            ])),
        ]);

        try {
            $client->post('/test', []);
            $this->fail('Expected BadRequestException');
        } catch (BadRequestException $e) {
            $this->assertEquals('INVALID_INPUT', $e->getErrorCode());
        }
    }

    public function testRetryAfterHttpDate(): void
    {
        $futureDate = gmdate('D, d M Y H:i:s', time() + 60) . ' GMT';
        $client = $this->createMockClient([
            new Response(429, ['Retry-After' => $futureDate], json_encode(['error' => 'Rate limited'])),
        ]);

        try {
            $client->get('/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            // Should be approximately 60 seconds
            $this->assertGreaterThan(50, $e->getRetryAfter());
            $this->assertLessThanOrEqual(60, $e->getRetryAfter());
        }
    }
}
