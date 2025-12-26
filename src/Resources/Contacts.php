<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Contacts
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Create a new contact.
     *
     * @param array{
     *     email: string,
     *     first_name?: string,
     *     last_name?: string,
     *     phone_number?: string,
     *     custom_fields?: array<string, mixed>,
     *     source?: string,
     *     consent_type?: 'explicit'|'implicit'|'legitimate_interest',
     *     consent_source?: string,
     *     consent_timestamp?: string,
     *     consent_ip_address?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->post('/contacts', $params) ?? [];
    }

    /**
     * Get a contact by ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get('/contacts/' . $id) ?? [];
    }

    /**
     * Update a contact.
     *
     * @param array{
     *     email?: string,
     *     first_name?: string,
     *     last_name?: string,
     *     phone_number?: string,
     *     custom_fields?: array<string, mixed>,
     *     consent_type?: 'explicit'|'implicit'|'legitimate_interest',
     *     consent_source?: string,
     *     consent_timestamp?: string,
     *     consent_ip_address?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->client->patch('/contacts/' . $id, $params) ?? [];
    }

    /**
     * Delete a contact.
     *
     * @return array<string, mixed>|null
     */
    public function delete(string $id): ?array
    {
        return $this->client->delete('/contacts/' . $id);
    }

    /**
     * List contacts with optional filters.
     *
     * @param array{
     *     status?: string,
     *     page?: int,
     *     limit?: int,
     *     search?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/contacts', $params) ?? [];
    }

    /**
     * Unsubscribe a contact.
     *
     * @return array<string, mixed>
     */
    public function unsubscribe(string $id): array
    {
        return $this->client->post('/contacts/' . $id . '/unsubscribe') ?? [];
    }

    /**
     * Resubscribe a contact.
     *
     * @return array<string, mixed>
     */
    public function resubscribe(string $id): array
    {
        return $this->client->post('/contacts/' . $id . '/resubscribe') ?? [];
    }
}
