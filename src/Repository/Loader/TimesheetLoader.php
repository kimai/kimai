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

final class TimesheetLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false, private bool $basicHydrated = true)
    {
    }

    /**
     * @param array<int|Timesheet> $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $ids = array_map(function ($timesheet) {
            if ($timesheet instanceof Timesheet) {
                // make sure that this potential doctrine proxy is initialized and filled with all data
                $timesheet->getType();

                return $timesheet->getId();
            }

            return $timesheet;
        }, $results);

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        /** @var array<Timesheet> $timesheets */
        $timesheets = $qb->select('PARTIAL t.{id}', 'project')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.project', 'project')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        $projectIds = array_map(function ($timesheet) {
            return $timesheet->getProject()->getId();
        }, $timesheets);

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
            $customerIds = array_map(function ($project) {
                return $project->getCustomer()->getId();
            }, $projects);

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL c.{id}', 'meta')
                ->from(Customer::class, 'c')
                ->leftJoin('c.meta', 'meta')
                ->andWhere($qb->expr()->in('c.id', $customerIds))
                ->getQuery()
                ->execute();
        }

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'activity')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.activity', 'activity')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        if ($this->fullyHydrated) {
            $activityIds = array_filter(array_map(function (Timesheet $timesheet) {
                return $timesheet->getActivity()?->getId();
            }, $timesheets), function ($id): bool {
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

        if ($this->basicHydrated) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL t.{id}', 'user')
                ->from(Timesheet::class, 't')
                ->leftJoin('t.user', 'user')
                ->andWhere($qb->expr()->in('t.id', $ids))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL t.{id}', 'tags')
                ->from(Timesheet::class, 't')
                ->leftJoin('t.tags', 'tags')
                ->andWhere($qb->expr()->in('t.id', $ids))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL t.{id}', 'meta')
                ->from(Timesheet::class, 't')
                ->leftJoin('t.meta', 'meta')
                ->andWhere($qb->expr()->in('t.id', $ids))
                ->getQuery()
                ->execute();
        }
    }
}
