<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\PanicAlert;
use Guard51\Entity\PanicAlertStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\PanicAlertRepository;
use Psr\Log\LoggerInterface;

final class PanicService
{
    public function __construct(
        private readonly PanicAlertRepository $panicRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function triggerPanic(string $tenantId, array $data): PanicAlert
    {
        if (empty($data['guard_id']) || !isset($data['lat']) || !isset($data['lng'])) {
            throw ApiException::validation('guard_id, lat, lng are required.');
        }

        $alert = new PanicAlert();
        $alert->setTenantId($tenantId)
            ->setGuardId($data['guard_id'])
            ->setLatitude((float) $data['lat'])
            ->setLongitude((float) $data['lng']);
        if (isset($data['site_id'])) $alert->setSiteId($data['site_id']);
        if (isset($data['message'])) $alert->setMessage($data['message']);
        if (isset($data['audio_url'])) $alert->setAudioUrl($data['audio_url']);

        $this->panicRepo->save($alert);

        // TODO: Push via WebSocket to admins/supervisors/dispatchers
        // TODO: Send SMS via Termii to emergency contacts
        // TODO: Send email via ZeptoMail

        $this->logger->critical('PANIC ALERT triggered.', [
            'alert_id' => $alert->getId(), 'guard_id' => $data['guard_id'],
            'lat' => $data['lat'], 'lng' => $data['lng'],
        ]);

        return $alert;
    }

    public function acknowledge(string $alertId, string $userId): PanicAlert
    {
        $alert = $this->panicRepo->findOrFail($alertId);
        if (!$alert->getStatus()->isActive()) throw ApiException::conflict('Alert is already resolved.');
        $alert->acknowledge($userId);
        $this->panicRepo->save($alert);
        return $alert;
    }

    public function markResponding(string $alertId): PanicAlert
    {
        $alert = $this->panicRepo->findOrFail($alertId);
        $alert->markResponding();
        $this->panicRepo->save($alert);
        return $alert;
    }

    public function resolve(string $alertId, string $userId, ?string $notes = null): PanicAlert
    {
        $alert = $this->panicRepo->findOrFail($alertId);
        $alert->resolve($userId, $notes);
        $this->panicRepo->save($alert);
        $this->logger->info('Panic alert resolved.', ['alert_id' => $alertId]);
        return $alert;
    }

    public function markFalseAlarm(string $alertId, string $userId, ?string $notes = null): PanicAlert
    {
        $alert = $this->panicRepo->findOrFail($alertId);
        $alert->markFalseAlarm($userId, $notes);
        $this->panicRepo->save($alert);
        return $alert;
    }

    public function getActiveAlerts(string $tenantId): array
    {
        return $this->panicRepo->findActiveByTenant($tenantId);
    }

    public function getRecentAlerts(string $tenantId, int $hours = 24): array
    {
        return $this->panicRepo->findRecentByTenant($tenantId, $hours);
    }
}
