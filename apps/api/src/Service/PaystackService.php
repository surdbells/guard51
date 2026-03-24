<?php

declare(strict_types=1);

namespace Guard51\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

final class PaystackService
{
    private Client $client;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new Client([
            'base_uri' => $this->config['base_url'] ?? 'https://api.paystack.co',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public function initializeTransaction(string $email, int $amountKobo, array $metadata = []): ?array
    {
        try {
            $response = $this->client->post('/transaction/initialize', [
                'json' => [
                    'email' => $email,
                    'amount' => $amountKobo,
                    'metadata' => $metadata,
                    'currency' => 'NGN',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            $this->logger->error('Paystack initialize failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function verifyTransaction(string $reference): ?array
    {
        try {
            $response = $this->client->get("/transaction/verify/{$reference}");
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            $this->logger->error('Paystack verify failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function createPlan(string $name, int $amountKobo, string $interval = 'monthly'): ?array
    {
        try {
            $response = $this->client->post('/plan', [
                'json' => [
                    'name' => $name,
                    'amount' => $amountKobo,
                    'interval' => $interval,
                    'currency' => 'NGN',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            $this->logger->error('Paystack create plan failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function validateWebhook(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->config['webhook_secret'] ?? '');
        return hash_equals($computed, $signature);
    }
}
