<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\PassdownLog;
use Guard51\Entity\PassdownPriority;
use Guard51\Exception\ApiException;
use Guard51\Repository\PassdownLogRepository;
use Psr\Log\LoggerInterface;

final class PassdownService
{
    public function __construct(
        private readonly PassdownLogRepository $passdownRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createPassdown(string $tenantId, array $data): PassdownLog
    {
        if (empty($data['site_id']) || empty($data['guard_id']) || empty($data['content'])) {
            throw ApiException::validation('site_id, guard_id, and content are required.');
        }
        $log = new PassdownLog();
        $log->setTenantId($tenantId)
            ->setSiteId($data['site_id'])
            ->setGuardId($data['guard_id'])
            ->setContent($data['content']);
        if (isset($data['shift_id'])) $log->setShiftId($data['shift_id']);
        if (isset($data['incoming_guard_id'])) $log->setIncomingGuardId($data['incoming_guard_id']);
        if (isset($data['priority'])) $log->setPriority(PassdownPriority::from($data['priority']));
        if (isset($data['attachments'])) $log->setAttachments($data['attachments']);
        $this->passdownRepo->save($log);
        $this->logger->info('Passdown created.', ['site_id' => $data['site_id']]);
        return $log;
    }

    public function listBySite(string $siteId, int $limit = 20): array
    {
        return $this->passdownRepo->findBySite($siteId, $limit);
    }

    public function listUnacknowledged(string $tenantId): array
    {
        return $this->passdownRepo->findUnacknowledged($tenantId);
    }

    public function acknowledge(string $passdownId, string $guardId): PassdownLog
    {
        $log = $this->passdownRepo->findOrFail($passdownId);
        if ($log->isAcknowledged()) throw ApiException::conflict('Already acknowledged.');
        $log->acknowledge($guardId);
        $this->passdownRepo->save($log);
        return $log;
    }
}
