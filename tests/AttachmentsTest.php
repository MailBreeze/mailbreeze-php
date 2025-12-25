<?php

declare(strict_types=1);

namespace MailBreeze\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailBreeze\MailBreeze;
use PHPUnit\Framework\TestCase;

class AttachmentsTest extends TestCase
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

    public function testCreateUploadUrl(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'attachment_id' => 'attach_123',
                'upload_url' => 'https://storage.example.com/upload/abc123',
                'expires_at' => '2024-01-01T01:00:00Z',
            ])),
        ]);

        $result = $client->attachments->createUploadUrl([
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024000,
        ]);

        $this->assertEquals('attach_123', $result['attachment_id']);
        $this->assertStringStartsWith('https://', $result['upload_url']);
    }

    public function testGetAttachment(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'attach_123',
                'filename' => 'document.pdf',
                'content_type' => 'application/pdf',
                'size' => 1024000,
                'status' => 'ready',
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $attachment = $client->attachments->get('attach_123');

        $this->assertEquals('attach_123', $attachment['id']);
        $this->assertEquals('document.pdf', $attachment['filename']);
        $this->assertEquals('ready', $attachment['status']);
    }

    public function testDeleteAttachment(): void
    {
        $client = $this->createClient([
            new Response(204, [], ''),
        ]);

        $result = $client->attachments->delete('attach_123');

        $this->assertNull($result);
    }

    public function testUploadUrlForImageAttachment(): void
    {
        $client = $this->createClient([
            new Response(201, [], json_encode([
                'attachment_id' => 'attach_456',
                'upload_url' => 'https://storage.example.com/upload/xyz789',
                'expires_at' => '2024-01-01T01:00:00Z',
            ])),
        ]);

        $result = $client->attachments->createUploadUrl([
            'filename' => 'logo.png',
            'content_type' => 'image/png',
            'size' => 50000,
        ]);

        $this->assertEquals('attach_456', $result['attachment_id']);
    }

    public function testGetAttachmentPendingStatus(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'id' => 'attach_789',
                'filename' => 'video.mp4',
                'content_type' => 'video/mp4',
                'size' => 10000000,
                'status' => 'pending',
                'created_at' => '2024-01-01T00:00:00Z',
            ])),
        ]);

        $attachment = $client->attachments->get('attach_789');

        $this->assertEquals('pending', $attachment['status']);
    }
}
