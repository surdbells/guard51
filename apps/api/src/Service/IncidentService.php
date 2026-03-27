<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\IncidentEscalation;
use Guard51\Entity\IncidentReport;
use Guard51\Entity\IncidentStatus;
use Guard51\Entity\IncidentType;
use Guard51\Entity\Severity;
use Guard51\Exception\ApiException;
use Guard51\Repository\IncidentEscalationRepository;
use Guard51\Repository\IncidentReportRepository;
use Psr\Log\LoggerInterface;

final class IncidentService
{
    public function __construct(
        private readonly IncidentReportRepository $incidentRepo,
        private readonly IncidentEscalationRepository $escalationRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createIncident(string $tenantId, array $data): IncidentReport
    {
        if (empty($data['guard_id']) || empty($data['site_id']) || empty($data['incident_type']) || empty($data['severity']) || empty($data['title']) || empty($data['description'])) {
            throw ApiException::validation('guard_id, site_id, incident_type, severity, title, description required.');
        }
        $ir = new IncidentReport();
        $ir->setTenantId($tenantId)->setGuardId($data['guard_id'])->setSiteId($data['site_id'])
            ->setIncidentType(IncidentType::from($data['incident_type']))->setSeverity(Severity::from($data['severity']))
            ->setTitle($data['title'])->setDescription($data['description']);
        if (isset($data['location_detail'])) $ir->setLocationDetail($data['location_detail']);
        if (isset($data['lat'])) $ir->setLatitude((float) $data['lat']);
        if (isset($data['lng'])) $ir->setLongitude((float) $data['lng']);
        if (isset($data['occurred_at'])) $ir->setOccurredAt(new \DateTimeImmutable($data['occurred_at']));
        if (isset($data['attachments'])) $ir->setAttachments($data['attachments']);
        if (isset($data['assigned_to'])) $ir->setAssignedTo($data['assigned_to']);

        $this->incidentRepo->save($ir);

        // Auto-escalate critical incidents
        if ($ir->getSeverity() === Severity::CRITICAL) {
            $ir->escalate();
            $this->incidentRepo->save($ir);
            $this->logger->critical('Critical incident auto-escalated.', ['id' => $ir->getId(), 'title' => $ir->getTitle()]);
        }

        return $ir;
    }

    public function updateStatus(string $incidentId, string $status): IncidentReport
    {
        $ir = $this->incidentRepo->findOrFail($incidentId);
        $ir->setStatus(IncidentStatus::from($status));
        $this->incidentRepo->save($ir);
        return $ir;
    }

    public function resolve(string $incidentId, string $userId, string $resolution): IncidentReport
    {
        $ir = $this->incidentRepo->findOrFail($incidentId);
        $ir->resolve($userId, $resolution);
        $this->incidentRepo->save($ir);
        return $ir;
    }

    public function escalate(string $incidentId, string $escalatedTo, string $escalatedBy, string $reason): IncidentReport
    {
        $ir = $this->incidentRepo->findOrFail($incidentId);
        $ir->escalate();
        $this->incidentRepo->save($ir);

        $esc = new IncidentEscalation();
        $esc->setIncidentId($incidentId)->setEscalatedTo($escalatedTo)->setEscalatedBy($escalatedBy)->setReason($reason);
        $this->escalationRepo->save($esc);

        return $ir;
    }

    public function getEscalations(string $incidentId): array { return $this->escalationRepo->findByIncident($incidentId); }

    public function listActive(string $tenantId): array { return $this->incidentRepo->findActiveByTenant($tenantId); }

    public function listFiltered(string $tenantId, ?string $siteId = null, ?string $severity = null, ?string $status = null): array
    {
        return $this->incidentRepo->findByTenantFiltered($tenantId, $siteId, $severity, $status);
    }
}
