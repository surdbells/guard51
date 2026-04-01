<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Invoice;

/** @extends BaseRepository<Invoice> */
class InvoiceRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Invoice::class; }

    public function findByTenant(string $tenantId, ?string $status = null, ?string $clientId = null): array
    {
        $qb = $this->createQueryBuilder('i')->where('i.tenantId = :tid');
        if ($status) $qb->andWhere('i.status = :s')->setParameter('s', $status);
        if ($clientId) $qb->andWhere('i.clientId = :cid')->setParameter('cid', $clientId);
        return $qb->orderBy('i.issueDate', 'DESC')->getQuery()->getResult();
    }

    public function findOverdue(string $tenantId): array
    {
        return $this->createQueryBuilder('i')->where('i.dueDate < :now')->andWhere('i.status IN (:active)')->setParameter('now', new \DateTimeImmutable())
            ->setParameter('active', ['sent', 'viewed', 'partially_paid'])
            ->orderBy('i.dueDate', 'ASC')->getQuery()->getResult();
    }

    public function getNextInvoiceNumber(string $tenantId): string
    {
        $count = $this->count([]);
        return 'INV-' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
