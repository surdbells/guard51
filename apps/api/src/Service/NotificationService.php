<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\DevicePlatform;
use Guard51\Entity\DeviceToken;
use Guard51\Entity\Notification;
use Guard51\Entity\NotificationChannel;
use Guard51\Entity\NotificationType;
use Guard51\Exception\ApiException;
use Guard51\Repository\DeviceTokenRepository;
use Guard51\Repository\NotificationRepository;
use Psr\Log\LoggerInterface;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notifRepo,
        private readonly DeviceTokenRepository $tokenRepo,
        private readonly RedisQueueService $queue,
        private readonly FcmService $fcm,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(string $tenantId, string $userId, NotificationType $type, string $title, string $body, array $data = [], NotificationChannel $channel = NotificationChannel::IN_APP): Notification
    {
        $n = new Notification();
        $n->setTenantId($tenantId)->setUserId($userId)->setType($type)->setTitle($title)->setBody($body)->setData($data)->setChannel($channel);
        $this->notifRepo->save($n);

        // Dispatch to async queue based on channel
        if ($channel === NotificationChannel::PUSH || $channel === NotificationChannel::IN_APP) {
            $devices = $this->tokenRepo->findActiveByUser($userId);
            $tokens = array_map(fn($d) => $d->getToken(), $devices);
            if ($tokens) {
                $this->queue->push('push', ['tokens' => $tokens, 'title' => $title, 'body' => $body, 'data' => $data]);
            }
        }
        if ($channel === NotificationChannel::SMS) {
            $this->queue->push('sms', ['user_id' => $userId, 'message' => "{$title}: {$body}"]);
        }
        if ($channel === NotificationChannel::EMAIL) {
            $this->queue->push('email', ['user_id' => $userId, 'subject' => $title, 'body' => $body]);
        }

        $n->markSent();
        $this->notifRepo->save($n);
        return $n;
    }

    public function getUserNotifications(string $userId, int $limit = 50): array { return $this->notifRepo->findByUser($userId, $limit); }
    public function getUnreadCount(string $userId): int { return $this->notifRepo->countUnread($userId); }

    public function markRead(string $notificationId): Notification
    {
        $n = $this->notifRepo->findOrFail($notificationId);
        $n->markRead();
        $this->notifRepo->save($n);
        return $n;
    }

    public function markAllRead(string $userId): int
    {
        $unread = $this->notifRepo->findBy(['userId' => $userId, 'isRead' => false]);
        foreach ($unread as $n) { $n->markRead(); $this->notifRepo->save($n); }
        return count($unread);
    }

    public function registerDevice(string $userId, string $token, string $platform): DeviceToken
    {
        $dt = new DeviceToken();
        $dt->setUserId($userId)->setToken($token)->setPlatform(DevicePlatform::from($platform));
        $this->tokenRepo->save($dt);
        return $dt;
    }

    public function getActiveDevices(string $userId): array { return $this->tokenRepo->findActiveByUser($userId); }
}
