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
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Activity>
 */
final class ActivityLoader implements LoaderInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<Activity> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        $activityIds = array_filter(array_unique(array_map(function (Activity $activity) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $activity->getName();
            // using reporting controller tests will show that error
            $activity->getProject()?->getName();

            return $activity->getId();
        }, $results)), function ($value) { return $value !== null; });

        $em = $this->entityManager;

        $projectIds = array_filter(array_unique(array_map(function (Activity $activity) {
            return $activity->getProject()?->getId();
        }, $results)), function ($value) { return $value !== null; });

        // global activities don't have projects
        if (\count($projectIds) > 0) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'customer')
                ->from(Project::class, 'project')
                ->leftJoin('project.customer', 'customer')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'teams')
                ->from(Project::class, 'project')
                ->leftJoin('project.teams', 'teams')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();

            $customerIds = array_filter(array_unique(array_map(function (Activity $activity) {
                return $activity->getProject()?->getCustomer()?->getId();
            }, $results)), function ($value) { return $value !== null; });

            if (\count($customerIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL customer.{id}', 'teams')
                    ->from(Customer::class, 'customer')
                    ->leftJoin('customer.teams', 'teams')
                    ->andWhere($qb->expr()->in('customer.id', $customerIds))
                    ->getQuery()
                    ->execute();
            }
        }

        // required on "Activity listing" page for non super-admins
        if (\count($activityIds) > 0) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL a.{id}', 'teams')
                ->from(Activity::class, 'a')
                ->leftJoin('a.teams', 'teams')
                ->andWhere($qb->expr()->in('a.id', $activityIds))
                ->getQuery()
                ->execute();
        }
    }
}
