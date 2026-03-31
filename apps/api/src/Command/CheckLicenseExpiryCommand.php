<?php
declare(strict_types=1);
namespace Guard51\Command;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\GuardLicense;

final class CheckLicenseExpiryCommand
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function execute(): int
    {
        $threshold = new \DateTimeImmutable('+30 days');
        $qb = $this->em->createQueryBuilder();
        $qb->select('gl')
            ->from(GuardLicense::class, 'gl')
            ->where('gl.expiryDate <= :threshold')
            ->andWhere('gl.expiryDate >= :today')
            ->andWhere('gl.expiryAlertSent = false')
            ->setParameter('threshold', $threshold)
            ->setParameter('today', new \DateTimeImmutable('today'));

        $licenses = $qb->getQuery()->getResult();
        $count = 0;

        foreach ($licenses as $license) {
            $license->setExpiryAlertSent(true);
            // TODO: Send notification to guard and supervisor
            $count++;
        }
        $this->em->flush();
        echo "Found {$count} licenses expiring within 30 days.\n";
        return 0;
    }
}
