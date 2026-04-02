<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\SupportTicket;
use Guard51\Exception\ApiException;
use Guard51\Repository\SupportTicketRepository;

final class SupportTicketService
{
    public function __construct(private readonly SupportTicketRepository $repo) {}

    public function create(string $tenantId, string $userId, array $data): SupportTicket
    {
        if (empty($data['subject']) || empty($data['description'])) throw ApiException::validation('Subject and description required.');
        $t = new SupportTicket();
        $t->setTenantId($tenantId)->setUserId($userId)->setSubject($data['subject'])->setDescription($data['description'])
            ->setPriority($data['priority'] ?? 'medium')->setCategory($data['category'] ?? null);
        $this->repo->save($t);
        return $t;
    }

    public function listByTenant(string $tenantId, ?string $status = null): array { return $this->repo->findByTenant($tenantId, $status); }
    public function listAll(?string $status = null): array
    {
        return $status ? $this->repo->findBy(['status' => $status], ['createdAt' => 'DESC']) : $this->repo->findAll();
    }

    public function resolve(string $id): SupportTicket { $t = $this->repo->findOrFail($id); $t->resolve(); $this->repo->save($t); return $t; }
    public function close(string $id): SupportTicket { $t = $this->repo->findOrFail($id); $t->close(); $this->repo->save($t); return $t; }
    public function assign(string $id, string $assignedTo): SupportTicket { $t = $this->repo->findOrFail($id); $t->setAssignedTo($assignedTo)->setStatus('in_progress'); $this->repo->save($t); return $t; }
    public function countOpen(): int { return $this->repo->countOpen(); }
}
