<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class ListsTest extends TestCase
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

    public function testCreateList(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'list_123',
                'name' => 'Newsletter',
                'description' => 'Weekly newsletter subscribers',
                'contact_count' => 0,
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $list = $client->lists->create([
            'name' => 'Newsletter',
            'description' => 'Weekly newsletter subscribers',
        ]);

        $this->assertEquals('list_123', $list['id']);
        $this->assertEquals('Newsletter', $list['name']);
    }

    public function testGetList(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'list_123',
                'name' => 'Newsletter',
                'contact_count' => 100,
            ])),
        ]);

        $list = $client->lists->get('list_123');

        $this->assertEquals('list_123', $list['id']);
        $this->assertEquals(100, $list['contact_count']);
    }

    public function testUpdateList(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'list_123',
                'name' => 'Updated Newsletter',
                'updated_at' => '2024-01-02T00:00:00Z',
            ])),
        ]);

        $list = $client->lists->update('list_123', [
            'name' => 'Updated Newsletter',
        ]);

        $this->assertEquals('Updated Newsletter', $list['name']);
    }

    public function testDeleteList(): void
    {
        $client = $this->createClient([
            new Response(204, [], ''),
        ]);

        $result = $client->lists->delete('list_123');

        $this->assertNull($result);
    }

    public function testListLists(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'list_1', 'name' => 'List A'],
                    ['id' => 'list_2', 'name' => 'List B'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->lists->list();

        $this->assertCount(2, $result['items']);
    }

    public function testGetListStats(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'total' => 1000,
                'active' => 900,
                'unsubscribed' => 80,
                'bounced' => 15,
                'complained' => 5,
            ])),
        ]);

        $stats = $client->lists->stats('list_123');

        $this->assertEquals(1000, $stats['total']);
        $this->assertEquals(900, $stats['active']);
    }

    public function testAddContactToList(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'added' => true,
            ])),
        ]);

        $result = $client->lists->addContact('list_123', 'contact_456');

        $this->assertTrue($result['added']);
    }

    public function testRemoveContactFromList(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'removed' => true,
            ])),
        ]);

        $result = $client->lists->removeContact('list_123', 'contact_456');

        $this->assertTrue($result['removed']);
    }

    public function testListContactsInList(): void
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

        $result = $client->lists->contacts('list_123');

        $this->assertCount(2, $result['items']);
    }
}
