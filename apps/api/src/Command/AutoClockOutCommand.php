<?php
declare(strict_types=1);
namespace Guard51\Command;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\TimeClock;
use Guard51\Entity\TimeClockStatus;

final class AutoClockOutCommand
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function execute(): int
    {
        // Find all clocked-in entries older than 14 hours
        $cutoff = new \DateTimeImmutable('-14 hours');
        $qb = $this->em->createQueryBuilder();
        $qb->select('tc')
            ->from(TimeClock::class, 'tc')
            ->where('tc.status = :status')
            ->andWhere('tc.clockInTime < :cutoff')
            ->setParameter('status', TimeClockStatus::CLOCKED_IN->value)
            ->setParameter('cutoff', $cutoff);

        $records = $qb->getQuery()->getResult();
        $count = 0;

        foreach ($records as $tc) {
            $tc->setStatus(TimeClockStatus::AUTO_CLOCKED_OUT);
            $tc->setClockOutTime(new \DateTimeImmutable());
            $count++;
        }
        $this->em->flush();
        echo "Auto clocked-out {$count} guards.\n";
        return 0;
    }
}
