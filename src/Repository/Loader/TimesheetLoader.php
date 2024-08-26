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
use App\Entity\Timesheet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Timesheet>
 */
final class TimesheetLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $fullyHydrated = false
    )
    {
    }

    /**
     * @param array<Timesheet> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        $ids = array_filter(array_unique(array_map(function (Timesheet $timesheet) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $timesheet->getType();

            return $timesheet->getId();
        }, $results)), function ($value) { return $value !== null; });

        $em = $this->entityManager;

        $projectIds = array_filter(array_unique(array_map(function (Timesheet $timesheet) {
            return $timesheet->getProject()?->getId();
        }, $results)), function ($value) { return $value !== null; });

        if ($this->fullyHydrated) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL p.{id}', 'meta')
                ->from(Project::class, 'p')
                ->leftJoin('p.meta', 'meta')
                ->andWhere($qb->expr()->in('p.id', $projectIds))
                ->getQuery()
                ->execute();
        }

        $qb = $em->createQueryBuilder();
        /** @var array<Project> $projects */
        $projects = $qb->select('PARTIAL p.{id}', 'customer')
            ->from(Project::class, 'p')
            ->leftJoin('p.customer', 'customer')
            ->andWhere($qb->expr()->in('p.id', $projectIds))
            ->getQuery()
            ->execute();

        if ($this->fullyHydrated) {
            $customerIds = array_filter(array_unique(array_map(function (Project $project) {
                return $project->getCustomer()->getId();
            }, $projects)), function ($value) { return $value !== null; });

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL c.{id}', 'meta')
                ->from(Customer::class, 'c')
                ->leftJoin('c.meta', 'meta')
                ->andWhere($qb->expr()->in('c.id', $customerIds))
                ->getQuery()
                ->execute();
        }

        if ($this->fullyHydrated) {
            $activityIds = array_filter(array_map(function (Timesheet $timesheet) {
                return $timesheet->getActivity()?->getId();
            }, $results), function ($id): bool {
                return $id !== null;
            });

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL a.{id}', 'meta')
                ->from(Activity::class, 'a')
                ->leftJoin('a.meta', 'meta')
                ->andWhere($qb->expr()->in('a.id', $activityIds))
                ->getQuery()
                ->execute();
        }

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'tags')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.tags', 'tags')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();
    }
}
