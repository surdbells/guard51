<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\DispatchAssignment;
use Guard51\Entity\DispatchCall;
use Guard51\Entity\DispatchCallType;
use Guard51\Entity\Severity;
use Guard51\Exception\ApiException;
use Guard51\Repository\DispatchAssignmentRepository;
use Guard51\Repository\DispatchCallRepository;
use Psr\Log\LoggerInterface;

final class DispatchService
{
    public function __construct(
        private readonly DispatchCallRepository $callRepo,
        private readonly DispatchAssignmentRepository $assignmentRepo,
        private readonly GeofenceService $geofenceService,
        private readonly LoggerInterface $logger,
    ) {}

    public function createCall(string $tenantId, array $data, string $createdBy): DispatchCall
    {
        if (empty($data['caller_name']) || empty($data['call_type']) || empty($data['priority']) || empty($data['description'])) {
            throw ApiException::validation('caller_name, call_type, priority, description required.');
        }
        $call = new DispatchCall();
        $call->setTenantId($tenantId)->setCallerName($data['caller_name'])
            ->setCallType(DispatchCallType::from($data['call_type']))->setPriority(Severity::from($data['priority']))
            ->setDescription($data['description'])->setCreatedBy($createdBy);
        if (isset($data['caller_phone'])) $call->setCallerPhone($data['caller_phone']);
        if (isset($data['client_id'])) $call->setClientId($data['client_id']);
        if (isset($data['site_id'])) $call->setSiteId($data['site_id']);
        $this->callRepo->save($call);
        return $call;
    }

    public function assignGuard(string $callId, string $guardId): DispatchAssignment
    {
        $call = $this->callRepo->findOrFail($callId);
        $call->dispatch();
        $this->callRepo->save($call);

        $assignment = new DispatchAssignment();
        $assignment->setDispatchId($callId)->setGuardId($guardId);
        $this->assignmentRepo->save($assignment);
        return $assignment;
    }

    public function updateAssignmentStatus(string $assignmentId, string $action): DispatchAssignment
    {
        $a = $this->assignmentRepo->findOrFail($assignmentId);
        match ($action) {
            'acknowledge' => $a->acknowledge(),
            'en_route' => $a->markEnRoute(),
            'on_scene' => $a->markOnScene(),
            'complete' => $a->complete(),
            default => throw ApiException::validation("Invalid action: {$action}"),
        };
        $this->assignmentRepo->save($a);
        return $a;
    }

    public function resolveCall(string $callId, string $resolution): DispatchCall
    {
        $call = $this->callRepo->findOrFail($callId);
        $call->resolve($resolution);
        $this->callRepo->save($call);
        return $call;
    }

    public function suggestNearestGuards(string $tenantId, float $lat, float $lng, int $limit = 5): array
    {
        return $this->geofenceService->findNearestGuards($tenantId, $lat, $lng, $limit);
    }

    public function getActiveCalls(string $tenantId): array { return $this->callRepo->findActiveByTenant($tenantId); }
    public function getRecentCalls(string $tenantId, int $hours = 24): array { return $this->callRepo->findByTenantRecent($tenantId, $hours); }
    public function getAssignments(string $callId): array { return $this->assignmentRepo->findByDispatch($callId); }
}
