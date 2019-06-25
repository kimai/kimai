<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Project;
use App\Entity\Timesheet;
use Doctrine\ORM\EntityManagerInterface;

final class TimesheetIdLoader implements LoaderInterface
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
        $projects = $qb->select('PARTIAL t.{id}', 'project')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.project', 'project')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        if (!empty($projects)) {
            $projectIds = array_map(function (Timesheet $timesheet) {
                return $timesheet->getProject()->getId();
            }, $projects);

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL p.{id}', 'customer')
                ->from(Project::class, 'p')
                ->leftJoin('p.customer', 'customer')
                ->andWhere($qb->expr()->in('p.id', $projectIds))
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
