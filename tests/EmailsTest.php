<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class EmailsTest extends TestCase
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

    public function testSendEmail(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'email_123',
                'from' => 'sender@example.com',
                'to' => ['recipient@example.com'],
                'subject' => 'Hello World',
                'status' => 'queued',
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $email = $client->emails->send([
            'from' => 'sender@example.com',
            'to' => ['recipient@example.com'],
            'subject' => 'Hello World',
            'html' => '<p>Hello!</p>',
        ]);

        $this->assertEquals('email_123', $email['id']);
        $this->assertEquals('queued', $email['status']);
    }

    public function testSendEmailWithTemplate(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'email_456',
                'status' => 'queued',
            ])),
        ]);

        $email = $client->emails->send([
            'from' => 'sender@example.com',
            'to' => ['recipient@example.com'],
            'template_id' => 'tmpl_123',
            'variables' => ['name' => 'John'],
        ]);

        $this->assertEquals('email_456', $email['id']);
    }

    public function testSendEmailWithAttachments(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'email_789',
                'status' => 'queued',
            ])),
        ]);

        $email = $client->emails->send([
            'from' => 'sender@example.com',
            'to' => ['recipient@example.com'],
            'subject' => 'With Attachment',
            'html' => '<p>See attached</p>',
            'attachment_ids' => ['attach_123', 'attach_456'],
        ]);

        $this->assertEquals('email_789', $email['id']);
    }

    public function testGetEmail(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'email_123',
                'status' => 'delivered',
                'delivered_at' => '2024-01-01T00:01:00Z',
            ])),
        ]);

        $email = $client->emails->get('email_123');

        $this->assertEquals('email_123', $email['id']);
        $this->assertEquals('delivered', $email['status']);
    }

    public function testListEmails(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'email_1', 'status' => 'sent'],
                    ['id' => 'email_2', 'status' => 'delivered'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->emails->list();

        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['meta']['total']);
    }

    public function testListEmailsWithFilters(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'email_1', 'status' => 'bounced'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 1,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->emails->list([
            'status' => 'bounced',
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertCount(1, $result['items']);
        $this->assertEquals('bounced', $result['items'][0]['status']);
    }

    public function testGetEmailStats(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'stats' => [
                    'total' => 1000,
                    'sent' => 950,
                    'failed' => 50,
                    'transactional' => 600,
                    'marketing' => 400,
                    'successRate' => 95.0,
                ],
            ])),
        ]);

        $stats = $client->emails->stats();

        $this->assertEquals(1000, $stats['total']);
        $this->assertEquals(950, $stats['sent']);
        $this->assertEquals(95.0, $stats['successRate']);
    }

    public function testCancelEmail(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'email_123',
                'cancelled' => true,
            ])),
        ]);

        $result = $client->emails->cancel('email_123');

        $this->assertTrue($result['cancelled']);
    }
}
