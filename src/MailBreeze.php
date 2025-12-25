<?php

declare(strict_types=1);

namespace MailBreeze;

use MailBreeze\Resources\Attachments;
use MailBreeze\Resources\Automations;
use MailBreeze\Resources\Contacts;
use MailBreeze\Resources\Emails;
use MailBreeze\Resources\Lists;
use MailBreeze\Resources\Verification;

class MailBreeze
{
    public readonly Emails $emails;
    public readonly Contacts $contacts;
    public readonly Lists $lists;
    public readonly Verification $verification;
    public readonly Automations $automations;
    public readonly Attachments $attachments;

    private HttpClient $httpClient;

    /**
     * @param array{
     *     http_client?: \GuzzleHttp\ClientInterface,
     *     base_url?: string,
     *     timeout?: int,
     *     max_retries?: int,
     *     retry_delay?: int,
     * } $options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $this->httpClient = new HttpClient($apiKey, $options);

        $this->emails = new Emails($this->httpClient);
        $this->contacts = new Contacts($this->httpClient);
        $this->lists = new Lists($this->httpClient);
        $this->verification = new Verification($this->httpClient);
        $this->automations = new Automations($this->httpClient);
        $this->attachments = new Attachments($this->httpClient);
    }
}
