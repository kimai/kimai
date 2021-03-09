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
use Doctrine\DBAL\Types\Types;

final class ProjectViewService
{
    /**
     * @var ProjectRepository
     */
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
     */
    public function getProjectView(ProjectViewQuery $query): array
    {
        $begin = $query->getBegin();
        $end = $query->getEnd();
        $user = $query->getUser();
        $today = new \DateTime('now', $begin->getTimezone());
        $startMonth = clone $begin;
        $startMonth->modify('first day of this month');
        $endMonth = clone $begin;
        $endMonth->modify('last day of this month');

        /*
        $dayDuration = $this->timesheetRepository->createQueryBuilder('t1');
        $dayDuration
            ->select('SUM(t1.duration)')
            ->andWhere('t1.project = p')
            ->andWhere('DATE(t1.begin) = :starting_date')
        ;

        $weekDuration = $this->timesheetRepository->createQueryBuilder('t2');
        $weekDuration
            ->select('SUM(t2.duration)')
            ->andWhere('t2.project = p')
            ->andWhere('DATE(t2.begin) BETWEEN :start_date AND :end_date')
        ;

        $monthDuration = $this->timesheetRepository->createQueryBuilder('t3');
        $monthDuration
            ->select('SUM(t3.duration) as monthDuration')
            ->andWhere('t3.project = p')
            ->andWhere('DATE(t3.begin) BETWEEN :start_month AND :end_month')
        ;

        $notExportedDuration = $this->timesheetRepository->createQueryBuilder('t4');
        $notExportedDuration
            ->select('SUM(t4.duration)')
            ->andWhere('t4.project = p')
            ->andWhere('t4.exported = :exported')
        ;

        $notExportedRate = $this->timesheetRepository->createQueryBuilder('t5');
        $notExportedRate
            ->select('SUM(t5.rate)')
            ->where('t5.project = p')
            ->andWhere('t5.exported = :exported')
        ;

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p AS project')
            ->addSelect('c')
            ->addSelect('(' . $dayDuration->getDQL() . ') AS today')
            ->addSelect('(' . $weekDuration->getDQL() . ') AS week')
            ->addSelect('(' . $monthDuration->getDQL() . ') AS month')
            ->addSelect('(' . $notExportedDuration->getDQL() . ') as notExportedDuration')
            ->addSelect('(' . $notExportedRate->getDQL() . ') as notExportedRate')
            ->addSelect('SUM(t.duration) AS total')
            ->addSelect('SUM(t.rate) AS totalRate')
            ->leftJoin('p.customer', 'c')
            ->leftJoin(Timesheet::class, 't', 'WITH', 'p.id = t.project')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->addGroupBy('p')
            ->addGroupBy('c')
            ->addGroupBy('t.project')
        ;

        if ($query->getCustomer() !== null) {
            $qb->andWhere($qb->expr()->eq('c', ':customer'));
            $qb->setParameter('customer', $query->getCustomer()->getId());
        }

        if (!$query->isIncludeNoWork()) {
            $qb->andHaving($qb->expr()->gt('total', 0));
        }

        if (!$query->isIncludeNoBudget()) {
            $qb->andWhere($qb->expr()->gt('p.timeBudget', 0));
        }

        $this->repository->addPermissionCriteria($qb, $user);

        $qb
            ->setParameter('starting_date', $today->format('Y-m-d'))
            ->setParameter('start_date', $begin->format('Y-m-d'))
            ->setParameter('end_date', $end->format('Y-m-d'))
            ->setParameter('start_month', $startMonth)
            ->setParameter('end_month', $endMonth)
            ->setParameter('exported', false, Types::BOOLEAN)
        ;

        $result = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($result as $res) {
            $entity = new ProjectViewModel();
            $entity->setProject($res['project']);
            $entity->setDurationDay($res['today'] ?? 0);
            $entity->setDurationWeek($res['week'] ?? 0);
            $entity->setDurationMonth($res['month'] ?? 0);
            $entity->setDurationTotal($res['total'] ?? 0);
            $entity->setRateTotal($res['totalRate'] ?? 0);
            $entity->setNotExportedRate($res['notExportedRate'] ?? 0);
            $entity->setNotExportedDuration($res['notExportedDuration'] ?? 0);

            $stats[] = $entity;
        }
        */

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p AS project')
            ->addSelect('SUM(t.duration) AS totalDuration')
            ->addSelect('SUM(t.rate) AS totalRate')
            //->addSelect('SUM(t.internalRate) AS totalInternalRate')
            ->leftJoin('p.customer', 'c')
            ->leftJoin(Timesheet::class, 't', 'WITH', 'p.id = t.project')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->addGroupBy('p')
            ->addGroupBy('t.project')
        ;

        if ($query->getCustomer() !== null) {
            $qb->andWhere($qb->expr()->eq('c', ':customer'));
            $qb->setParameter('customer', $query->getCustomer()->getId());
        }

        if (!$query->isIncludeNoWork()) {
            $qb->andHaving($qb->expr()->gt('totalDuration', 0));
        }

        if (!$query->isIncludeNoBudget()) {
            $qb->andWhere($qb->expr()->gt('p.timeBudget', 0));
        }

        $this->repository->addPermissionCriteria($qb, $user);

        $result = $qb->getQuery()->getResult();

        $projectViews = [];
        foreach ($result as $res) {
            $entity = new ProjectViewModel();
            $entity->setProject($res['project']);
            $entity->setDurationTotal($res['totalDuration'] ?? 0);
            $entity->setRateTotal($res['totalRate'] ?? 0);
            //$entity->setRateTotal($res['totalInternalRate'] ?? 0);

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
            // TODO this assumes that start and end date is really a week
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

        // values for the all time (not exported)
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

        return array_values($projectViews);
    }
}
