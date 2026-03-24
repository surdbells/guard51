<?php

declare(strict_types=1);

namespace Guard51\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

final class TermiiService
{
    private Client $client;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new Client([
            'base_uri' => $this->config['base_url'] ?? 'https://api.ng.termii.com/api',
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);
    }

    public function sendSms(string $to, string $message): bool
    {
        if (empty($this->config['api_key'])) {
            $this->logger->warning('Termii API key not configured. SMS not sent.', ['to' => $to]);
            return false;
        }

        try {
            $this->client->post('/sms/send', [
                'json' => [
                    'to' => $to,
                    'from' => $this->config['sender_id'],
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => 'generic',
                    'api_key' => $this->config['api_key'],
                ],
            ]);
            $this->logger->info('SMS sent via Termii.', ['to' => $to]);
            return true;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to send SMS via Termii.', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendOtp(string $to): bool
    {
        if (empty($this->config['api_key'])) {
            $this->logger->warning('Termii API key not configured. OTP not sent.', ['to' => $to]);
            return false;
        }

        try {
            $this->client->post('/sms/otp/send', [
                'json' => [
                    'api_key' => $this->config['api_key'],
                    'message_type' => 'NUMERIC',
                    'to' => $to,
                    'from' => $this->config['sender_id'],
                    'channel' => 'generic',
                    'pin_attempts' => 3,
                    'pin_time_to_live' => 10,
                    'pin_length' => 6,
                    'pin_placeholder' => '< 1234 >',
                    'message_text' => 'Your Guard51 verification code is < 1234 >. Valid for 10 minutes.',
                    'pin_type' => 'NUMERIC',
                ],
            ]);
            $this->logger->info('OTP sent via Termii.', ['to' => $to]);
            return true;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to send OTP via Termii.', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
