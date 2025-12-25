<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Emails
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Send an email.
     *
     * @param array{
     *     from: string,
     *     to: array<string>,
     *     subject?: string,
     *     html?: string,
     *     text?: string,
     *     template_id?: string,
     *     variables?: array<string, mixed>,
     *     attachment_ids?: array<string>,
     *     reply_to?: string,
     *     cc?: array<string>,
     *     bcc?: array<string>,
     *     headers?: array<string, string>,
     *     tags?: array<string>,
     * } $params
     * @return array<string, mixed>
     */
    public function send(array $params): array
    {
        return $this->client->post('/emails', $params) ?? [];
    }

    /**
     * Get an email by ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get('/emails/' . $id) ?? [];
    }

    /**
     * List emails with optional filters.
     *
     * @param array{
     *     status?: string,
     *     page?: int,
     *     limit?: int,
     *     from_date?: string,
     *     to_date?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/emails', $params) ?? [];
    }

    /**
     * Get email statistics.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->client->get('/emails/stats') ?? [];
    }

    /**
     * Cancel a pending email.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->client->post('/emails/' . $id . '/cancel') ?? [];
    }
}
