<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

final class ActivityLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param array<int|Activity> $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $ids = array_map(function ($activity) {
            if ($activity instanceof Activity) {
                // make sure that this potential doctrine proxy is initialized and filled with all data
                $activity->getName();

                return $activity->getId();
            }

            return $activity;
        }, $results);

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        /** @var Activity[] $activities */
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
            $projectIds = array_unique(array_map(function (Activity $activity) {
                if (null === $activity->getProject()) {
                    return null;
                }

                return $activity->getProject()->getId();
            }, $activities));

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'customer')
                ->from(Project::class, 'project')
                ->leftJoin('project.customer', 'customer')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();

            $customerIds = array_unique(array_map(function (Activity $activity) {
                if (null === $activity->getProject()) {
                    return null;
                }

                return $activity->getProject()->getCustomer()->getId();
            }, $activities));

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'teams')
                ->from(Project::class, 'project')
                ->leftJoin('project.teams', 'teams')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL customer.{id}', 'teams')
                ->from(Customer::class, 'customer')
                ->leftJoin('customer.teams', 'teams')
                ->andWhere($qb->expr()->in('customer.id', $customerIds))
                ->getQuery()
                ->execute();
        }

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL a.{id}', 'teams')
            ->from(Activity::class, 'a')
            ->leftJoin('a.teams', 'teams')
            ->andWhere($qb->expr()->in('a.id', $ids))
            ->getQuery()
            ->execute();

        // do not load team members or leads by default, because they will only be used on detail pages
        // and there is no benefit in adding multiple queries for most requests when they are only needed in one place
        if ($this->fullyHydrated) {
            $teamIds = [];
            foreach ($activities as $activity) {
                foreach ($activity->getTeams() as $team) {
                    $teamIds[] = $team->getId();
                }
            }
            $teamIds = array_unique($teamIds);

            if (\count($teamIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL team.{id}', 'members', 'user')
                    ->from(Team::class, 'team')
                    ->leftJoin('team.members', 'members')
                    ->leftJoin('members.user', 'user')
                    ->andWhere($qb->expr()->in('team.id', $teamIds))
                    ->getQuery()
                    ->execute();
            }
        }
    }
}
