<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<ActivityRate>
 */
class ActivityRateRepository extends EntityRepository
{
    public function saveRate(ActivityRate $rate): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rate);
        $entityManager->flush();
    }

    public function deleteRate(ActivityRate $rate): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($rate);
        $entityManager->flush();
    }

    /**
     * @param Activity $activity
     * @return ActivityRate[]
     */
    public function getRatesForActivity(Activity $activity): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, a')
            ->from(ActivityRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.activity', 'a')
            ->andWhere(
                $qb->expr()->eq('r.activity', ':activity')
            )
            ->addOrderBy('u.alias')
            ->setParameter('activity', $activity)
        ;

        return $qb->getQuery()->getResult();
    }
}
