<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Payslip;

/** @extends BaseRepository<Payslip> */
class PayslipRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Payslip::class; }
    public function findByGuard(string $guardId): array { return $this->findBy(['guardId' => $guardId], ['createdAt' => 'DESC']); }
}
