<?php

declare(strict_types=1);

namespace MailBreeze\Resources;

use MailBreeze\HttpClient;

class Automations
{
    public function __construct(private HttpClient $client)
    {
    }

    /**
     * Enroll a contact in an automation.
     *
     * @param array{
     *     automation_id: string,
     *     contact_id: string,
     *     variables?: array<string, mixed>,
     * } $params
     * @return array<string, mixed>
     */
    public function enroll(array $params): array
    {
        return $this->client->post('/automations/enrollments', $params) ?? [];
    }

    /**
     * Get an enrollment by ID.
     *
     * @return array<string, mixed>
     */
    public function getEnrollment(string $id): array
    {
        return $this->client->get('/automations/enrollments/' . $id) ?? [];
    }

    /**
     * List enrollments with optional filters.
     *
     * @param array{
     *     automation_id?: string,
     *     status?: string,
     *     page?: int,
     *     limit?: int,
     * } $params
     * @return array<string, mixed>
     */
    public function listEnrollments(array $params = []): array
    {
        return $this->client->get('/automations/enrollments', $params) ?? [];
    }

    /**
     * Cancel an enrollment.
     *
     * @return array<string, mixed>
     */
    public function cancelEnrollment(string $id): array
    {
        return $this->client->post('/automations/enrollments/' . $id . '/cancel') ?? [];
    }
}
