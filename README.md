# MailBreeze PHP SDK

Official PHP SDK for [MailBreeze](https://mailbreeze.com) - Email Marketing & Transactional Email Platform.

[![CI](https://github.com/MailBreeze/mailbreeze-php/actions/workflows/ci.yml/badge.svg)](https://github.com/MailBreeze/mailbreeze-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/mailbreeze/mailbreeze-php/v)](https://packagist.org/packages/mailbreeze/mailbreeze-php)
[![License](https://poser.pugx.org/mailbreeze/mailbreeze-php/license)](https://packagist.org/packages/mailbreeze/mailbreeze-php)

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require mailbreeze/mailbreeze-php
```

## Quick Start

```php
<?php

use MailBreeze\MailBreeze;

$client = new MailBreeze('your_api_key');

// Send an email
$email = $client->emails->send([
    'from' => 'sender@yourdomain.com',
    'to' => ['recipient@example.com'],
    'subject' => 'Hello from MailBreeze!',
    'html' => '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
]);

echo "Email sent with ID: " . $email['id'];
```

## Features

- **Transactional Emails** - Send individual emails with tracking
- **Contacts Management** - Create, update, and manage contacts
- **Lists** - Organize contacts into lists
- **Email Verification** - Validate email addresses
- **Automations** - Enroll contacts in automation workflows
- **Attachments** - Upload and attach files to emails

## Usage Examples

### Sending Emails

```php
// Simple email
$email = $client->emails->send([
    'from' => 'hello@yourdomain.com',
    'to' => ['user@example.com'],
    'subject' => 'Welcome!',
    'html' => '<p>Hello World</p>',
    'text' => 'Hello World',
]);

// With template
$email = $client->emails->send([
    'from' => 'hello@yourdomain.com',
    'to' => ['user@example.com'],
    'template_id' => 'tmpl_welcome',
    'variables' => [
        'name' => 'John',
        'company' => 'Acme Inc',
    ],
]);

// With attachments
$email = $client->emails->send([
    'from' => 'hello@yourdomain.com',
    'to' => ['user@example.com'],
    'subject' => 'Your Invoice',
    'html' => '<p>Please find your invoice attached.</p>',
    'attachment_ids' => ['attach_123'],
]);

// Get email status
$email = $client->emails->get('email_123');

// List emails
$result = $client->emails->list([
    'status' => 'delivered',
    'page' => 1,
    'limit' => 20,
]);

// Get statistics
$stats = $client->emails->stats();
```

### Managing Contacts

```php
// Create contact
$contact = $client->contacts->create([
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'custom_fields' => [
        'company' => 'Acme Inc',
        'plan' => 'enterprise',
    ],
]);

// Update contact
$contact = $client->contacts->update('contact_123', [
    'first_name' => 'Johnny',
]);

// Get contact
$contact = $client->contacts->get('contact_123');

// List contacts
$result = $client->contacts->list([
    'status' => 'active',
    'search' => 'john',
]);

// Unsubscribe contact
$client->contacts->unsubscribe('contact_123');

// Resubscribe contact
$client->contacts->resubscribe('contact_123');

// Delete contact
$client->contacts->delete('contact_123');
```

### Managing Lists

```php
// Create list
$list = $client->lists->create([
    'name' => 'Newsletter Subscribers',
    'description' => 'Weekly newsletter recipients',
]);

// Add contact to list
$client->lists->addContact('list_123', 'contact_456');

// Remove contact from list
$client->lists->removeContact('list_123', 'contact_456');

// Get list contacts
$result = $client->lists->contacts('list_123');

// Get list statistics
$stats = $client->lists->stats('list_123');
```

### Email Verification

```php
// Verify single email
$result = $client->verification->verify('user@example.com');

if ($result['is_valid']) {
    echo "Email is valid!";
} else {
    echo "Email is invalid: " . $result['status'];
}

// Batch verification
$batch = $client->verification->verifyBatch([
    'email1@example.com',
    'email2@example.com',
    'email3@example.com',
]);

// Check batch status
$result = $client->verification->getBatchStatus($batch['verification_id']);

// Get verification statistics
$stats = $client->verification->stats();
```

### Automations

```php
// Enroll contact in automation
$enrollment = $client->automations->enroll([
    'automation_id' => 'auto_welcome',
    'contact_id' => 'contact_123',
    'variables' => [
        'discount_code' => 'WELCOME10',
    ],
]);

// Get enrollment status
$enrollment = $client->automations->getEnrollment('enrollment_123');

// List enrollments
$result = $client->automations->listEnrollments([
    'automation_id' => 'auto_welcome',
    'status' => 'active',
]);

// Cancel enrollment
$client->automations->cancelEnrollment('enrollment_123');
```

### Attachments

```php
// Create upload URL
$upload = $client->attachments->createUploadUrl([
    'filename' => 'invoice.pdf',
    'content_type' => 'application/pdf',
    'size' => 102400,
]);

// Upload file to the URL
// Use your preferred HTTP client to PUT the file to $upload['upload_url']

// Get attachment
$attachment = $client->attachments->get($upload['attachment_id']);

// Delete attachment
$client->attachments->delete('attach_123');
```

## Configuration Options

```php
$client = new MailBreeze('your_api_key', [
    'base_url' => 'https://api.mailbreeze.com/v1', // Custom API URL
    'timeout' => 30,                                // Request timeout in seconds
    'max_retries' => 3,                             // Maximum retry attempts
    'retry_delay' => 1000,                          // Base retry delay in milliseconds
]);
```

## Error Handling

The SDK throws specific exceptions for different error types:

```php
use MailBreeze\Exceptions\AuthenticationException;
use MailBreeze\Exceptions\BadRequestException;
use MailBreeze\Exceptions\NotFoundException;
use MailBreeze\Exceptions\RateLimitException;
use MailBreeze\Exceptions\ValidationException;
use MailBreeze\Exceptions\ServerException;

try {
    $email = $client->emails->send([...]);
} catch (AuthenticationException $e) {
    // Invalid API key (401)
    echo "Authentication failed: " . $e->getMessage();
} catch (ValidationException $e) {
    // Validation errors (422)
    echo "Validation failed: " . $e->getMessage();
    print_r($e->getErrors()); // Field-specific errors
} catch (RateLimitException $e) {
    // Rate limited (429)
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (NotFoundException $e) {
    // Resource not found (404)
    echo "Not found: " . $e->getMessage();
} catch (BadRequestException $e) {
    // Bad request (400)
    echo "Bad request: " . $e->getMessage();
} catch (ServerException $e) {
    // Server error (5xx)
    echo "Server error: " . $e->getMessage();
}
```

## Automatic Retries

The SDK automatically retries failed requests with exponential backoff for:
- Connection errors
- Server errors (500, 502, 503, 504)
- Request timeouts (408)

Rate limit errors (429) are not retried automatically to respect the API limits.

## License

MIT License - see [LICENSE](LICENSE) for details.
