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
     * @return array<string, mixed>
     */
    public function verify(string $email): array
    {
        return $this->client->post('/verification/verify', ['email' => $email]) ?? [];
    }

    /**
     * Verify multiple email addresses in batch.
     *
     * @param array<string> $emails
     * @return array<string, mixed>
     */
    public function verifyBatch(array $emails): array
    {
        return $this->client->post('/verification/batch', ['emails' => $emails]) ?? [];
    }

    /**
     * Get batch verification status.
     *
     * @return array<string, mixed>
     */
    public function getBatchStatus(string $verificationId): array
    {
        return $this->client->get('/verification/batch/' . $verificationId) ?? [];
    }

    /**
     * Get verification statistics.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->client->get('/verification/stats') ?? [];
    }
}
