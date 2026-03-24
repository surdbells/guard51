<?php

declare(strict_types=1);

namespace Guard51\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

final class ZeptoMailService
{
    private const BASE_URL = 'https://api.zeptomail.com/v1.1';
    private Client $client;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'headers' => [
                'Authorization' => 'Zoho-enczapikey ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Send a transactional email via ZeptoMail.
     *
     * @param string $toEmail    Recipient email address
     * @param string $toName     Recipient name
     * @param string $subject    Email subject
     * @param string $htmlBody   HTML email body
     * @param string|null $textBody  Plain text fallback (optional)
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
    ): bool {
        if (empty($this->config['api_key'])) {
            $this->logger->warning('ZeptoMail API key not configured. Email not sent.', [
                'to' => $toEmail,
                'subject' => $subject,
            ]);
            return false;
        }

        $payload = [
            'from' => [
                'address' => $this->config['from_email'],
                'name' => $this->config['from_name'],
            ],
            'to' => [
                ['email_address' => ['address' => $toEmail, 'name' => $toName]],
            ],
            'subject' => $subject,
            'htmlbody' => $htmlBody,
        ];

        if ($textBody !== null) {
            $payload['textbody'] = $textBody;
        }

        try {
            $response = $this->client->post('/email', [
                'json' => $payload,
            ]);

            $this->logger->info('Email sent successfully via ZeptoMail.', [
                'to' => $toEmail,
                'subject' => $subject,
                'status' => $response->getStatusCode(),
            ]);

            return true;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to send email via ZeptoMail.', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a test email to verify configuration.
     */
    public function sendTestEmail(string $toEmail): bool
    {
        return $this->send(
            toEmail: $toEmail,
            toName: 'Guard51 Admin',
            subject: 'Guard51 — ZeptoMail Configuration Test',
            htmlBody: '<h2>Guard51</h2><p>ZeptoMail is configured correctly. This is a test email.</p>',
            textBody: 'Guard51 - ZeptoMail is configured correctly. This is a test email.',
        );
    }
}
