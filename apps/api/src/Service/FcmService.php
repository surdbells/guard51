<?php
declare(strict_types=1);
namespace Guard51\Service;

use Psr\Log\LoggerInterface;

/**
 * Firebase Cloud Messaging service for push notifications.
 * Sends to Android (FCM) and iOS (APNs via FCM).
 */
final class FcmService
{
    private string $serverKey;
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->serverKey = $_ENV['FCM_SERVER_KEY'] ?? '';
    }

    /** Send push to a single device token */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        return $this->send([
            'to' => $deviceToken,
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data' => $data,
            'priority' => 'high',
        ]);
    }

    /** Send push to a topic (e.g. tenant-specific channel) */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        return $this->send([
            'to' => '/topics/' . $topic,
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data' => $data,
            'priority' => 'high',
        ]);
    }

    /** Send to multiple device tokens (max 1000) */
    public function sendToDevices(array $tokens, string $title, string $body, array $data = []): bool
    {
        if (empty($tokens)) return false;
        return $this->send([
            'registration_ids' => array_slice($tokens, 0, 1000),
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data' => $data,
            'priority' => 'high',
        ]);
    }

    private function send(array $payload): bool
    {
        if (empty($this->serverKey)) {
            $this->logger->warning('FCM server key not configured. Push notification skipped.');
            return false;
        }

        $ch = curl_init(self::FCM_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $this->serverKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logger->error('FCM push failed.', ['http_code' => $httpCode, 'response' => $response]);
            return false;
        }

        $this->logger->info('FCM push sent.', ['response' => substr($response, 0, 200)]);
        return true;
    }
}
