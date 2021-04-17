<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Entity\Timesheet;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use Doctrine\DBAL\Types\Types;
use Exception;

final class ProjectViewService
{
    private $repository;
    private $timesheetRepository;

    public function __construct(ProjectRepository $projectRepository, TimesheetRepository $timesheetRepository)
    {
        $this->repository = $projectRepository;
        $this->timesheetRepository = $timesheetRepository;
    }

    /**
     * @param ProjectViewQuery $query
     * @return ProjectViewModel[]
     * @throws Exception
     */
    public function getProjectView(ProjectViewQuery $query): array
    {
        $factory = new DateTimeFactory($query->getToday()->getTimezone());
        $user = $query->getUser();
        $today = clone $query->getToday();

        $begin = $factory->getStartOfWeek($today);
        $end = $factory->getEndOfWeek($today);
        $startMonth = (clone $begin)->modify('first day of this month');
        $endMonth = (clone $begin)->modify('last day of this month');

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p AS project')
            ->addSelect('SUM(t.duration) AS totalDuration')
            ->addSelect('SUM(t.rate) AS totalRate')
            ->leftJoin('p.customer', 'c')
            ->leftJoin(Timesheet::class, 't', 'WITH', 'p.id = t.project')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.end'),
                    $qb->expr()->gte('p.end', ':project_end')
                )
            )
            ->addGroupBy('p')
            ->addGroupBy('t.project')
            ->setParameter('project_end', $today, Types::DATETIME_MUTABLE)
        ;

        if ($query->getCustomer() !== null) {
            $qb->andWhere($qb->expr()->eq('c', ':customer'));
            $qb->setParameter('customer', $query->getCustomer()->getId());
        }

        if (!$query->isIncludeNoWork()) {
            $qb->andHaving($qb->expr()->gt('totalDuration', 0));
        }

        if (!$query->isIncludeNoBudget()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('p.timeBudget', 0),
                    $qb->expr()->gt('p.budget', 0)
                )
            );
        }

        $this->repository->addPermissionCriteria($qb, $user);

        $result = $qb->getQuery()->getResult();

        $projectViews = [];
        foreach ($result as $res) {
            $entity = new ProjectViewModel($res['project']);
            $entity->setDurationTotal($res['totalDuration'] ?? 0);
            $entity->setRateTotal($res['totalRate'] ?? 0.00);

            $projectViews[$entity->getProject()->getId()] = $entity;
        }

        $projectIds = array_keys($projectViews);

        // values for today
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('DATE(t.begin) = :starting_date')
            ->groupBy('t.project')
            ->setParameter('starting_date', $today->format('Y-m-d'))
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationDay($row['duration']);
        }

        // values for the current week
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('DATE(t.begin) BETWEEN :start_date AND :end_date')
            ->groupBy('t.project')
            ->setParameter('start_date', $begin->format('Y-m-d'))
            ->setParameter('end_date', $end->format('Y-m-d'))
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationWeek($row['duration']);
        }

        // values for the current month
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('DATE(t.begin) BETWEEN :start_month AND :end_month')
            ->groupBy('t.project')
            ->setParameter('start_month', $startMonth)
            ->setParameter('end_month', $endMonth)
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationMonth($row['duration']);
        }

        // values for all time (not exported)
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration, SUM(t.rate) AS rate')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('t.exported = :exported')
            ->groupBy('t.project')
            ->setParameter('exported', false, Types::BOOLEAN)
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setNotExportedDuration($row['duration']);
            $projectViews[$row['id']]->setNotExportedRate($row['rate']);
        }

        // values for all time (not exported and billable)
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration, SUM(t.rate) AS rate')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('t.exported = :exported')
            ->andWhere('t.billable = :billable')
            ->groupBy('t.project')
            ->setParameter('exported', false, Types::BOOLEAN)
            ->setParameter('billable', true, Types::BOOLEAN)
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setNotBilledDuration($row['duration']);
            $projectViews[$row['id']]->setNotBilledRate($row['rate']);
        }

        // values for all time (none billable)
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, SUM(t.duration) AS duration, SUM(t.rate) AS rate')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('t.billable = :billable')
            ->groupBy('t.project')
            ->setParameter('billable', false, Types::BOOLEAN)
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setNoneBillableDuration($row['duration']);
            $projectViews[$row['id']]->setNoneBillableRate($row['rate']);
        }

        return array_values($projectViews);
    }
}
