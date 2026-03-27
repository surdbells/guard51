<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\CustomReportSubmission;

/** @extends BaseRepository<CustomReportSubmission> */
class CustomReportSubmissionRepository extends BaseRepository
{
    protected function getEntityClass(): string { return CustomReportSubmission::class; }

    public function findByTemplate(string $templateId, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')->where('c.templateId = :tid')->setParameter('tid', $templateId)
            ->orderBy('c.submittedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
