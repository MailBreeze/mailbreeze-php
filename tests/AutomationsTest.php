<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class AutomationsTest extends TestCase
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

    public function testEnrollContact(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'enrollment_123',
                'automation_id' => 'auto_456',
                'contact_id' => 'contact_789',
                'status' => 'active',
                'current_step' => 0,
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $enrollment = $client->automations->enroll([
            'automation_id' => 'auto_456',
            'contact_id' => 'contact_789',
        ]);

        $this->assertEquals('enrollment_123', $enrollment['id']);
        $this->assertEquals('active', $enrollment['status']);
    }

    public function testEnrollContactWithVariables(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'id' => 'enrollment_456',
                'variables' => [
                    'discount_code' => 'WELCOME10',
                    'trial_days' => 14,
                ],
            ])),
        ]);

        $enrollment = $client->automations->enroll([
            'automation_id' => 'auto_456',
            'contact_id' => 'contact_789',
            'variables' => [
                'discount_code' => 'WELCOME10',
                'trial_days' => 14,
            ],
        ]);

        $this->assertEquals('WELCOME10', $enrollment['variables']['discount_code']);
    }

    public function testGetEnrollment(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'enrollment_123',
                'automation_id' => 'auto_456',
                'status' => 'active',
                'current_step' => 2,
            ])),
        ]);

        $enrollment = $client->automations->getEnrollment('enrollment_123');

        $this->assertEquals('enrollment_123', $enrollment['id']);
        $this->assertEquals(2, $enrollment['current_step']);
    }

    public function testListEnrollments(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'enrollment_1', 'status' => 'active'],
                    ['id' => 'enrollment_2', 'status' => 'completed'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->automations->listEnrollments();

        $this->assertCount(2, $result['items']);
    }

    public function testListEnrollmentsWithFilters(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'enrollment_1', 'status' => 'active'],
                ],
                'meta' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 1,
                    'total_pages' => 1,
                ],
            ])),
        ]);

        $result = $client->automations->listEnrollments([
            'automation_id' => 'auto_456',
            'status' => 'active',
        ]);

        $this->assertCount(1, $result['items']);
        $this->assertEquals('active', $result['items'][0]['status']);
    }

    public function testCancelEnrollment(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'enrollment_123',
                'cancelled' => true,
            ])),
        ]);

        $result = $client->automations->cancelEnrollment('enrollment_123');

        $this->assertTrue($result['cancelled']);
    }
}
