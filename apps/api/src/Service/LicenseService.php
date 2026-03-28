<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\GuardLicense;
use Guard51\Entity\LicenseType;
use Guard51\Exception\ApiException;
use Guard51\Repository\GuardLicenseRepository;
use Psr\Log\LoggerInterface;

final class LicenseService
{
    public function __construct(private readonly GuardLicenseRepository $repo, private readonly LoggerInterface $logger) {}

    public function create(string $tenantId, array $data): GuardLicense
    {
        if (empty($data['guard_id']) || empty($data['license_type']) || empty($data['name']) || empty($data['issue_date']) || empty($data['expiry_date']))
            throw ApiException::validation('guard_id, license_type, name, issue_date, expiry_date required.');
        $l = new GuardLicense();
        $l->setTenantId($tenantId)->setGuardId($data['guard_id'])->setLicenseType(LicenseType::from($data['license_type']))
            ->setName($data['name'])->setIssueDate(new \DateTimeImmutable($data['issue_date']))
            ->setExpiryDate(new \DateTimeImmutable($data['expiry_date']));
        if (isset($data['license_number'])) $l->setLicenseNumber($data['license_number']);
        if (isset($data['issuing_authority'])) $l->setIssuingAuthority($data['issuing_authority']);
        if (isset($data['document_url'])) $l->setDocumentUrl($data['document_url']);
        $this->repo->save($l);
        return $l;
    }

    public function listByGuard(string $guardId): array { return $this->repo->findByGuard($guardId); }
    public function findExpiringSoon(string $tenantId, int $days = 30): array { return $this->repo->findExpiringSoon($tenantId, $days); }
    public function findExpired(string $tenantId): array { return $this->repo->findExpired($tenantId); }

    public function detectExpiryAlerts(string $tenantId): array
    {
        $expiring = $this->repo->findExpiringSoon($tenantId, 30);
        $alerts = [];
        foreach ($expiring as $l) {
            if (!$l->toArray()['expiry_alert_sent'] ?? false) {
                $l->markAlertSent(); $this->repo->save($l);
                $alerts[] = $l->toArray();
            }
        }
        $this->logger->info('License expiry check', ['tenant' => $tenantId, 'alerts' => count($alerts)]);
        return $alerts;
    }
}
