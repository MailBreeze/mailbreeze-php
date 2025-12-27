<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Verification
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Verify a single email address.
     *
     * @param array{email: string} $params Parameters containing email to verify
     * @return array<string, mixed>
     */
    public function verify(array $params): array
    {
        return $this->client->post('/email-verification/single', $params) ?? [];
    }

    /**
     * Verify multiple email addresses in batch.
     *
     * @param array<string> $emails
     * @return array<string, mixed>
     */
    public function batch(array $emails): array
    {
        return $this->client->post('/email-verification/batch', ['emails' => $emails]) ?? [];
    }

    /**
     * Get batch verification status.
     *
     * @return array<string, mixed>
     */
    public function get(string $verificationId): array
    {
        return $this->client->get('/email-verification/' . $verificationId) ?? [];
    }

    /**
     * Get verification statistics.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->client->get('/email-verification/stats') ?? [];
    }
}
