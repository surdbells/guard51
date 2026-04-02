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
        $qb = $this->createQueryBuilder('i')->where('i.tenantId = :tid')->setParameter('tid', $tenantId)
            ->orderBy('i.dueDate', 'ASC')->getQuery()->getResult();
    }

    public function getNextInvoiceNumber(string $tenantId): string
    {
        $count = $this->count(['tenantId' => $tenantId]);
        return 'INV-' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
