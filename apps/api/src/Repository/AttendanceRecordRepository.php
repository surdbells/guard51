<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\AttendanceRecord;

/** @extends BaseRepository<AttendanceRecord> */
class AttendanceRecordRepository extends BaseRepository
{
    protected function getEntityClass(): string { return AttendanceRecord::class; }

    public function findByTenantAndDate(string $tenantId, \DateTimeImmutable $date): array
    {
        return $this->findBy(['attendanceDate' => $date]);
    }

    public function findByGuardAndDateRange(string $guardId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('ar')
            ->where('ar.guardId = :gid')->andWhere('ar.attendanceDate >= :start')->andWhere('ar.attendanceDate <= :end')
            ->setParameter('gid', $guardId)->setParameter('start', $start)->setParameter('end', $end)
            ->orderBy('ar.attendanceDate', 'DESC')->getQuery()->getResult();
    }

    public function findUnreconciled(string $tenantId): array
    {
        return $this->findBy(['reconciled' => false]);
    }
}
