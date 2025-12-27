<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class VerificationTest extends TestCase
{
    private function createClient(array $responses): MailBreeze
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        return new MailBreeze('test_api_key', [
            'http_client' => $guzzle,
        ]);
    }

    public function testVerifySingleEmail(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'email' => 'valid@example.com',
                'status' => 'valid',
                'is_valid' => true,
                'is_disposable' => false,
                'is_role_based' => false,
                'is_free_provider' => false,
                'mx_found' => true,
                'smtp_check' => true,
            ])),
        ]);

        $result = $client->verification->verify(['email' => 'valid@example.com']);

        $this->assertEquals('valid@example.com', $result['email']);
        $this->assertEquals('valid', $result['status']);
        $this->assertTrue($result['is_valid']);
    }

    public function testVerifyInvalidEmail(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'email' => 'invalid@nonexistent.domain',
                'status' => 'invalid',
                'is_valid' => false,
                'is_disposable' => false,
                'is_role_based' => false,
                'is_free_provider' => false,
                'mx_found' => false,
            ])),
        ]);

        $result = $client->verification->verify(['email' => 'invalid@nonexistent.domain']);

        $this->assertFalse($result['is_valid']);
        $this->assertFalse($result['mx_found']);
    }

    public function testVerifyDisposableEmail(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'email' => 'test@tempmail.com',
                'status' => 'risky',
                'is_valid' => true,
                'is_disposable' => true,
            ])),
        ]);

        $result = $client->verification->verify(['email' => 'test@tempmail.com']);

        $this->assertEquals('risky', $result['status']);
        $this->assertTrue($result['is_disposable']);
    }

    public function testVerifyWithSuggestion(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'email' => 'user@gmial.com',
                'status' => 'invalid',
                'is_valid' => false,
                'suggestion' => 'user@gmail.com',
            ])),
        ]);

        $result = $client->verification->verify(['email' => 'user@gmial.com']);

        $this->assertEquals('user@gmail.com', $result['suggestion']);
    }

    public function testBatch(): void
    {
        $client = $this->createClient([
            new Response(202, [], json_encode([
                'verification_id' => 'batch_123',
                'status' => 'processing',
                'total' => 3,
                'processed' => 0,
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $result = $client->verification->batch([
            'email1@example.com',
            'email2@example.com',
            'email3@example.com',
        ]);

        $this->assertEquals('batch_123', $result['verification_id']);
        $this->assertEquals('processing', $result['status']);
        $this->assertEquals(3, $result['total']);
    }

    public function testGet(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'verification_id' => 'batch_123',
                'status' => 'completed',
                'total' => 3,
                'processed' => 3,
                'results' => [
                    ['email' => 'email1@example.com', 'status' => 'valid', 'is_valid' => true],
                    ['email' => 'email2@example.com', 'status' => 'invalid', 'is_valid' => false],
                    ['email' => 'email3@example.com', 'status' => 'risky', 'is_valid' => true],
                ],
                'completed_at' => '2024-01-01T00:01:00Z',
            ])),
        ]);

        $result = $client->verification->get('batch_123');

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(3, $result['processed']);
        $this->assertCount(3, $result['results']);
    }

    public function testGetVerificationStats(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'totalVerified' => 10000,
                'totalValid' => 8500,
                'totalInvalid' => 1000,
                'totalUnknown' => 100,
                'totalVerifications' => 500,
                'validPercentage' => 85.0,
            ])),
        ]);

        $stats = $client->verification->stats();

        $this->assertEquals(10000, $stats['totalVerified']);
        $this->assertEquals(8500, $stats['totalValid']);
        $this->assertEquals(85.0, $stats['validPercentage']);
    }

    public function testListVerifications(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'verif_1', 'status' => 'completed', 'total' => 100],
                    ['id' => 'verif_2', 'status' => 'processing', 'total' => 50],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->verification->list();

        $this->assertCount(2, $result['items']);
        $this->assertEquals('verif_1', $result['items'][0]['id']);
    }

    public function testListVerificationsWithFilters(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'verif_1', 'status' => 'completed', 'total' => 100],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 1,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->verification->list([
            'status' => 'completed',
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertCount(1, $result['items']);
        $this->assertEquals('completed', $result['items'][0]['status']);
    }
}
