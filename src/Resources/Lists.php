<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Lists
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Create a new list.
     *
     * @param array{
     *     name: string,
     *     description?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->post('/lists', $params) ?? [];
    }

    /**
     * Get a list by ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->get('/lists/' . $id) ?? [];
    }

    /**
     * Update a list.
     *
     * @param array{
     *     name?: string,
     *     description?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->client->patch('/lists/' . $id, $params) ?? [];
    }

    /**
     * Delete a list.
     *
     * @return array<string, mixed>|null
     */
    public function delete(string $id): ?array
    {
        return $this->client->delete('/lists/' . $id);
    }

    /**
     * List all lists with optional filters.
     *
     * @param array{
     *     page?: int,
     *     limit?: int,
     *     search?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/lists', $params) ?? [];
    }

    /**
     * Get list statistics.
     *
     * @return array<string, mixed>
     */
    public function stats(string $id): array
    {
        return $this->client->get('/lists/' . $id . '/stats') ?? [];
    }

    /**
     * Add a contact to a list.
     *
     * @return array<string, mixed>
     */
    public function addContact(string $listId, string $contactId): array
    {
        return $this->client->post('/lists/' . $listId . '/contacts/' . $contactId) ?? [];
    }

    /**
     * Remove a contact from a list.
     *
     * @return array<string, mixed>
     */
    public function removeContact(string $listId, string $contactId): array
    {
        return $this->client->delete('/lists/' . $listId . '/contacts/' . $contactId) ?? [];
    }

    /**
     * List contacts in a list.
     *
     * @param array{
     *     page?: int,
     *     limit?: int,
     * } $params
     * @return array<string, mixed>
     */
    public function contacts(string $listId, array $params = []): array
    {
        return $this->client->get('/lists/' . $listId . '/contacts', $params) ?? [];
    }
}
