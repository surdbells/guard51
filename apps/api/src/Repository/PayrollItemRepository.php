<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\PayrollItem;

/** @extends BaseRepository<PayrollItem> */
class PayrollItemRepository extends BaseRepository
{
    protected function getEntityClass(): string { return PayrollItem::class; }
    public function findByPeriod(string $periodId): array { return $this->findBy(['payrollPeriodId' => $periodId]); }
    public function findByGuard(string $guardId): array { return $this->findBy(['guardId' => $guardId], ['createdAt' => 'DESC']); }
}
