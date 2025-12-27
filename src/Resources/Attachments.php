<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Attachments
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Create a pre-signed upload URL.
     *
     * @param array{
     *     filename: string,
     *     content_type: string,
     *     size: int,
     * } $params
     * @return array<string, mixed>
     */
    public function createUploadUrl(array $params): array
    {
        return $this->client->post('/api/v1/attachments/presigned-url', $params) ?? [];
    }

    /**
     * Get an attachment by ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get('/api/v1/attachments/' . $id) ?? [];
    }

    /**
     * Delete an attachment.
     *
     * @return array<string, mixed>|null
     */
    public function delete(string $id): ?array
    {
        return $this->client->delete('/api/v1/attachments/' . $id);
    }
}
