<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Activity;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class ActivityIdLoader implements LoaderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int[] $ids
     */
    public function loadResults(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $activities = $qb->select('PARTIAL a.{id}', 'project')
            ->from(Activity::class, 'a')
            ->leftJoin('a.project', 'project')
            ->andWhere($qb->expr()->isNotNull('a.project'))
            ->andWhere($qb->expr()->in('a.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL a.{id}', 'meta')
            ->from(Activity::class, 'a')
            ->leftJoin('a.meta', 'meta')
            ->andWhere($qb->expr()->in('a.id', $ids))
            ->getQuery()
            ->execute();

        // global activities don't have projects
        if (!empty($activities)) {
            $projectIds = array_map(function (Activity $activity) {
                if (null === $activity->getProject()) {
                    return null;
                }

                return $activity->getProject()->getId();
            }, $activities);

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL p.{id}', 'customer')
                ->from(Project::class, 'p')
                ->leftJoin('p.customer', 'customer')
                ->andWhere($qb->expr()->in('p.id', $projectIds))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL a.{id}', 'PARTIAL project.{id}', 'teams', 'teamlead')
                ->from(Activity::class, 'a')
                ->leftJoin('a.project', 'project')
                ->leftJoin('project.teams', 'teams')
                ->leftJoin('teams.teamlead', 'teamlead')
                ->andWhere($qb->expr()->in('a.id', $ids))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL a.{id}', 'PARTIAL project.{id}', 'PARTIAL customer.{id}', 'teams', 'teamlead')
                ->from(Activity::class, 'a')
                ->leftJoin('a.project', 'project')
                ->leftJoin('project.customer', 'customer')
                ->leftJoin('customer.teams', 'teams')
                ->leftJoin('teams.teamlead', 'teamlead')
                ->andWhere($qb->expr()->in('a.id', $ids))
                ->getQuery()
                ->execute();
        }

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL a.{id}', 'teams', 'teamlead')
            ->from(Activity::class, 'a')
            ->leftJoin('a.teams', 'teams')
            ->leftJoin('teams.teamlead', 'teamlead')
            ->andWhere($qb->expr()->in('a.id', $ids))
            ->getQuery()
            ->execute();
    }
}
