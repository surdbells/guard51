<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\AuditAction;
use Guard51\Entity\AuditLog;
use Guard51\Repository\AuditLogRepository;
use Psr\Http\Message\ServerRequestInterface;

final class AuditService
{
    public function __construct(private readonly AuditLogRepository $repo) {}

    public function log(string $tenantId, ?string $userId, AuditAction $action, string $resourceType, ?string $resourceId = null, ?string $description = null, array $metadata = [], ?ServerRequestInterface $request = null): AuditLog
    {
        $log = new AuditLog();
        $log->setTenantId($tenantId)->setUserId($userId)->setAction($action)->setResourceType($resourceType)
            ->setResourceId($resourceId)->setDescription($description)->setMetadata($metadata);
        if ($request) {
            $log->setIpAddress($request->getServerParams()['REMOTE_ADDR'] ?? null);
            $log->setUserAgent($request->getHeaderLine('User-Agent') ?: null);
        }
        $this->repo->save($log);
        return $log;
    }

    public function getByTenant(string $tenantId, int $limit = 100): array { return $this->repo->findByTenant($tenantId, $limit); }
    public function getByUser(string $userId, int $limit = 50): array { return $this->repo->findByUser($userId, $limit); }
}
