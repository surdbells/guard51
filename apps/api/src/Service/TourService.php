<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\CheckpointType;
use Guard51\Entity\ScanMethod;
use Guard51\Entity\TourCheckpoint;
use Guard51\Entity\TourCheckpointScan;
use Guard51\Entity\TourSession;
use Guard51\Entity\TourSessionStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\TourCheckpointRepository;
use Guard51\Repository\TourCheckpointScanRepository;
use Guard51\Repository\TourSessionRepository;
use Psr\Log\LoggerInterface;

final class TourService
{
    public function __construct(
        private readonly TourCheckpointRepository $checkpointRepo,
        private readonly TourSessionRepository $sessionRepo,
        private readonly TourCheckpointScanRepository $scanRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Checkpoints ──────────────────────────────────

    public function createCheckpoint(string $tenantId, string $siteId, array $data): TourCheckpoint
    {
        if (empty($data['name']) || empty($data['checkpoint_type'])) {
            throw ApiException::validation('name and checkpoint_type are required.');
        }
        $cp = new TourCheckpoint();
        $cp->setTenantId($tenantId)->setSiteId($siteId)
            ->setName($data['name'])
            ->setCheckpointType(CheckpointType::from($data['checkpoint_type']));
        if (isset($data['qr_code_value'])) $cp->setQrCodeValue($data['qr_code_value']);
        if (isset($data['nfc_tag_id'])) $cp->setNfcTagId($data['nfc_tag_id']);
        if (isset($data['latitude'])) $cp->setLatitude((float) $data['latitude']);
        if (isset($data['longitude'])) $cp->setLongitude((float) $data['longitude']);
        if (isset($data['virtual_radius'])) $cp->setVirtualRadius((int) $data['virtual_radius']);
        if (isset($data['sequence_order'])) $cp->setSequenceOrder((int) $data['sequence_order']);
        if (isset($data['is_required'])) $cp->setIsRequired((bool) $data['is_required']);
        $this->checkpointRepo->save($cp);
        return $cp;
    }

    public function updateCheckpoint(string $checkpointId, array $data): TourCheckpoint
    {
        $cp = $this->checkpointRepo->findOrFail($checkpointId);
        if (isset($data['name'])) $cp->setName($data['name']);
        if (isset($data['sequence_order'])) $cp->setSequenceOrder((int) $data['sequence_order']);
        if (isset($data['is_required'])) $cp->setIsRequired((bool) $data['is_required']);
        if (isset($data['is_active'])) $cp->setIsActive((bool) $data['is_active']);
        if (isset($data['latitude'])) $cp->setLatitude((float) $data['latitude']);
        if (isset($data['longitude'])) $cp->setLongitude((float) $data['longitude']);
        if (isset($data['virtual_radius'])) $cp->setVirtualRadius((int) $data['virtual_radius']);
        $this->checkpointRepo->save($cp);
        return $cp;
    }

    public function listCheckpoints(string $siteId): array
    {
        return $this->checkpointRepo->findBySite($siteId);
    }

    // ── Tour Sessions ────────────────────────────────

    public function startTour(string $tenantId, string $guardId, string $siteId, ?string $shiftId = null): TourSession
    {
        $checkpointCount = $this->checkpointRepo->countBySite($siteId);
        if ($checkpointCount === 0) throw ApiException::conflict('No active checkpoints configured for this site.');

        $session = new TourSession();
        $session->setTenantId($tenantId)->setGuardId($guardId)->setSiteId($siteId)
            ->setShiftId($shiftId)->setTotalCheckpoints($checkpointCount);
        $this->sessionRepo->save($session);
        $this->logger->info('Tour started.', ['guard_id' => $guardId, 'site_id' => $siteId, 'checkpoints' => $checkpointCount]);
        return $session;
    }

    public function recordScan(string $sessionId, array $data): TourCheckpointScan
    {
        if (empty($data['checkpoint_id']) || empty($data['scan_method']) || !isset($data['lat']) || !isset($data['lng'])) {
            throw ApiException::validation('checkpoint_id, scan_method, lat, lng are required.');
        }
        $session = $this->sessionRepo->findOrFail($sessionId);
        if ($session->getStatus() !== TourSessionStatus::IN_PROGRESS) {
            throw ApiException::conflict('Tour session is not in progress.');
        }

        $scan = new TourCheckpointScan();
        $scan->setSessionId($sessionId)->setCheckpointId($data['checkpoint_id'])
            ->setScanMethod(ScanMethod::from($data['scan_method']))
            ->setLatitude((float) $data['lat'])->setLongitude((float) $data['lng']);
        if (isset($data['notes'])) $scan->setNotes($data['notes']);
        if (isset($data['photo_url'])) $scan->setPhotoUrl($data['photo_url']);

        $this->scanRepo->save($scan);
        $session->recordScan();
        $this->sessionRepo->save($session);

        // Auto-complete if all checkpoints scanned
        if ($session->getScannedCheckpoints() >= $session->getTotalCheckpoints()) {
            $session->complete();
            $this->sessionRepo->save($session);
        }

        return $scan;
    }

    public function completeTour(string $sessionId): TourSession
    {
        $session = $this->sessionRepo->findOrFail($sessionId);
        $session->complete();
        $this->sessionRepo->save($session);
        return $session;
    }

    public function getSessionDetail(string $sessionId): array
    {
        $session = $this->sessionRepo->findOrFail($sessionId);
        $scans = $this->scanRepo->findBySession($sessionId);
        return ['session' => $session->toArray(), 'scans' => array_map(fn($s) => $s->toArray(), $scans)];
    }

    public function listSessionsBySite(string $siteId, int $limit = 20): array
    {
        return $this->sessionRepo->findBySite($siteId, $limit);
    }

    public function listSessionsByGuard(string $guardId, int $limit = 20): array
    {
        return $this->sessionRepo->findByGuard($guardId, $limit);
    }
}
