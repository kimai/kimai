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
use App\Entity\User;
use App\Repository\Query\TimesheetQuery;
use App\Repository\Query\TimesheetQueryHint;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Timesheet>
 */
final class TimesheetLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ?TimesheetQuery $query = null
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

        if ($this->query !== null && $this->query->hasQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS)) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL p.{id}', 'meta')
                ->from(Project::class, 'p')
                ->leftJoin('p.meta', 'meta')
                ->andWhere($qb->expr()->in('p.id', $projectIds))
                ->getQuery()
                ->execute();
        }

        if (\count($projectIds) > 0) {
            $qb = $em->createQueryBuilder();
            /** @var array<Project> $projects */
            $projects = $qb->select('PARTIAL p.{id}', 'customer')
                ->from(Project::class, 'p')
                ->leftJoin('p.customer', 'customer')
                ->andWhere($qb->expr()->in('p.id', $projectIds))
                ->getQuery()
                ->execute();

            if ($this->query !== null && $this->query->hasQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS)) {
                $customerIds = array_filter(array_unique(array_map(function (Project $project) {
                    return $project->getCustomer()?->getId();
                }, $projects)), function ($value) { return $value !== null; });

                if (\count($customerIds) > 0) {
                    $qb = $em->createQueryBuilder();
                    $qb->select('PARTIAL c.{id}', 'meta')
                        ->from(Customer::class, 'c')
                        ->leftJoin('c.meta', 'meta')
                        ->andWhere($qb->expr()->in('c.id', $customerIds))
                        ->getQuery()
                        ->execute();
                }
            }
        }

        if ($this->query !== null && $this->query->hasQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS)) {
            $activityIds = array_filter(array_map(function (Timesheet $timesheet) {
                return $timesheet->getActivity()?->getId();
            }, $results), function ($id): bool {
                return $id !== null;
            });

            if (\count($activityIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL a.{id}', 'meta')
                    ->from(Activity::class, 'a')
                    ->leftJoin('a.meta', 'meta')
                    ->andWhere($qb->expr()->in('a.id', $activityIds))
                    ->getQuery()
                    ->execute();
            }
        }

        if ($this->query !== null && $this->query->hasQueryHint(TimesheetQueryHint::USER_PREFERENCES)) {
            $userIds = array_filter(array_map(function (Timesheet $timesheet) {
                return $timesheet->getUser()?->getId();
            }, $results), function ($id): bool {
                return $id !== null;
            });

            if (\count($userIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL u.{id}', 'preferences')
                    ->from(User::class, 'u')
                    ->leftJoin('u.preferences', 'preferences')
                    ->andWhere($qb->expr()->in('u.id', $userIds))
                    ->getQuery()
                    ->execute();
            }
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
