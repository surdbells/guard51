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
        private readonly LoggerInterface $logger,
    ) {}

    public function create(string $tenantId, string $userId, NotificationType $type, string $title, string $body, array $data = [], NotificationChannel $channel = NotificationChannel::IN_APP): Notification
    {
        $n = new Notification();
        $n->setTenantId($tenantId)->setUserId($userId)->setType($type)->setTitle($title)->setBody($body)->setData($data)->setChannel($channel);
        $this->notifRepo->save($n);

        if ($channel === NotificationChannel::PUSH) {
            // TODO: Firebase Cloud Messaging
            $this->logger->info('Push notification queued', ['user' => $userId, 'title' => $title]);
        } elseif ($channel === NotificationChannel::SMS) {
            // TODO: Termii SMS
            $this->logger->info('SMS notification queued', ['user' => $userId]);
        } elseif ($channel === NotificationChannel::EMAIL) {
            // TODO: ZeptoMail
            $this->logger->info('Email notification queued', ['user' => $userId]);
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
