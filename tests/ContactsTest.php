<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class ContactsTest extends TestCase
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

    public function testCreateContact(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'contact_123',
                'email' => 'john@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $contact = $client->contacts->create([
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('contact_123', $contact['id']);
        $this->assertEquals('john@example.com', $contact['email']);
        $this->assertEquals('active', $contact['status']);
    }

    public function testCreateContactWithCustomFields(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'contact_456',
                'email' => 'jane@example.com',
                'custom_fields' => [
                    'company' => 'Acme Inc',
                    'plan' => 'enterprise',
                ],
            ])),
        ]);

        $contact = $client->contacts->create([
            'email' => 'jane@example.com',
            'custom_fields' => [
                'company' => 'Acme Inc',
                'plan' => 'enterprise',
            ],
        ]);

        $this->assertEquals('Acme Inc', $contact['custom_fields']['company']);
    }

    public function testGetContact(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'contact_123',
                'email' => 'john@example.com',
                'status' => 'active',
            ])),
        ]);

        $contact = $client->contacts->get('contact_123');

        $this->assertEquals('contact_123', $contact['id']);
    }

    public function testUpdateContact(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'contact_123',
                'first_name' => 'Johnny',
                'updated_at' => '2024-01-02T00:00:00Z',
            ])),
        ]);

        $contact = $client->contacts->update('contact_123', [
            'first_name' => 'Johnny',
        ]);

        $this->assertEquals('Johnny', $contact['first_name']);
    }

    public function testDeleteContact(): void
    {
        $client = $this->createClient([
            new Response(204, [], ''),
        ]);

        $result = $client->contacts->delete('contact_123');

        $this->assertNull($result);
    }

    public function testListContacts(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'contact_1', 'email' => 'a@example.com'],
                    ['id' => 'contact_2', 'email' => 'b@example.com'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->contacts->list();

        $this->assertCount(2, $result['items']);
    }

    public function testListContactsWithFilters(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'contact_1', 'email' => 'john@example.com'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 1,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->contacts->list([
            'search' => 'john',
            'status' => 'active',
        ]);

        $this->assertCount(1, $result['items']);
    }

    public function testUnsubscribeContact(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'contact_123',
                'status' => 'unsubscribed',
            ])),
        ]);

        $contact = $client->contacts->unsubscribe('contact_123');

        $this->assertEquals('unsubscribed', $contact['status']);
    }

    public function testResubscribeContact(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'contact_123',
                'status' => 'active',
            ])),
        ]);

        $contact = $client->contacts->resubscribe('contact_123');

        $this->assertEquals('active', $contact['status']);
    }
}
