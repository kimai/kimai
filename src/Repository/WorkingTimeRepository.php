<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Entity\WorkingTime;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<WorkingTime>
 * @internal use WorkingTimeService instead!
 */
class WorkingTimeRepository extends EntityRepository
{
    private bool $pendingUpdate = false;

    public function deleteWorkingTime(WorkingTime $workingTime): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($workingTime);
        $entityManager->flush();
    }

    public function saveWorkingTime(WorkingTime $workingTime): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($workingTime);
        $entityManager->flush();
    }

    public function scheduleWorkingTimeUpdate(WorkingTime $workingTime): void
    {
        $this->pendingUpdate = true;
        $this->getEntityManager()->persist($workingTime);
    }

    public function persistScheduledWorkingTimes(): void
    {
        if ($this->pendingUpdate) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<WorkingTime>
     */
    public function findForYear(User $user, \DateTimeInterface $year): array
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('w')
            ->where($qb->expr()->eq('w.user', ':user'))
            ->setParameter('user', $user->getId())
            ->andWhere($qb->expr()->eq('YEAR(w.date)', ':date'))
            ->setParameter('date', $year->format('Y'))
            ->indexBy('w', 'w.date')
            ->orderBy('w.date')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @deprecated since 2.25.0 - kept for BC with old plugin versions
     */
    public function getLatestApproval(User $user): ?WorkingTime
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('MAX(DATE(w.date))')
            ->where($qb->expr()->eq('w.user', ':user'))
            ->setParameter('user', $user->getId())
            ->andWhere($qb->expr()->isNotNull('w.approvedAt'))
        ;

        $date = $qb->getQuery()->getSingleScalarResult();

        if ($date === null) {
            return null;
        }

        $qb = $this->createQueryBuilder('w');
        $qb->select('w')
            ->where($qb->expr()->eq('w.user', ':user'))
            ->setParameter('user', $user->getId())
            ->andWhere($qb->expr()->eq('DATE(w.date)', 'DATE(:date)'))
            ->setParameter('date', $date)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getLatestApprovalDate(User $user): ?\DateTimeInterface
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select($qb->expr()->max('(DATE(w.date))'))
            ->where($qb->expr()->eq('w.user', ':user'))
            ->setParameter('user', $user->getId())
            ->andWhere($qb->expr()->isNotNull('w.approvedAt'))
        ;

        $date = $qb->getQuery()->getSingleScalarResult();

        if ($date === null) {
            return null;
        }

        return new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone($user->getTimezone()));
    }
}
