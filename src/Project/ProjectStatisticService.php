<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Project;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\ProjectStatisticEvent;
use App\Model\ActivityStatistic;
use App\Model\ProjectStatistic;
use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use App\Reporting\ProjectDetails\ProjectDetailsModel;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use App\Reporting\ProjectInactive\ProjectInactiveQuery;
use App\Reporting\ProjectView\ProjectViewModel;
use App\Reporting\ProjectView\ProjectViewQuery;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ProjectStatisticService
{
    private $repository;
    private $timesheetRepository;
    private $dispatcher;

    public function __construct(ProjectRepository $projectRepository, TimesheetRepository $timesheetRepository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $projectRepository;
        $this->timesheetRepository = $timesheetRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getProjectStatistics(Project $project, ?DateTime $end = null): ProjectStatistic
    {
        $statistic = $this->repository->getProjectStatistics($project, null, $end);
        $event = new ProjectStatisticEvent($project, $statistic);
        $this->dispatcher->dispatch($event);

        return $statistic;
    }

    /**
     * @param ProjectInactiveQuery $query
     * @return Project[]
     */
    public function findInactiveProjects(ProjectInactiveQuery $query): array
    {
        $user = $query->getUser();
        $lastChange = clone $query->getLastChange();
        $now = new DateTime('now', $lastChange->getTimezone());

        $qb2 = $this->repository->createQueryBuilder('t1');
        $qb2
            ->select('1')
            ->from(Timesheet::class, 't')
            ->andWhere('p = t.project')
            ->andWhere($qb2->expr()->gte('t.begin', ':begin'))
        ;

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p, c')
            ->leftJoin('p.customer', 'c')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->andWhere($qb->expr()->not($qb->expr()->exists($qb2)))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.end'),
                    $qb->expr()->gte('p.end', ':project_end')
                )
            )
            ->setParameter('project_end', $now, Types::DATETIME_MUTABLE)
            ->setParameter('begin', $lastChange, Types::DATETIME_MUTABLE)
        ;

        $this->repository->addPermissionCriteria($qb, $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ProjectDetailsQuery $query
     * @return ProjectDetailsModel
     */
    public function getProjectsDetails(ProjectDetailsQuery $query): ProjectDetailsModel
    {
        $model = new ProjectDetailsModel($query->getProject());

        $years = [];
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('SUM(t.duration) as duration')
            ->addSelect('SUM(t.rate) as rate')
            ->addSelect('SUM(t.internalRate) as internalRate')
            ->andWhere('t.project = :project')
            ->setParameter('project', $query->getProject())
        ;

        // fetch grouped by activity for all time
        $qb1 = clone $qb;
        $qb1
            ->leftJoin(Activity::class, 'a', Join::WITH, 'a.id = t.activity')
            ->addSelect('a.id as id')
            ->addSelect('a.name as name')
            ->addSelect('a.color as color')
            ->addSelect('COUNT(t.id) as count')
            ->addGroupBy('id')
            ->addGroupBy('name')
            ->addGroupBy('color')
        ;
        foreach ($qb1->getQuery()->getResult() as $tmp) {
            $activity = new ActivityStatistic();
            $activity->setId($tmp['id']);
            $activity->setName($tmp['name']);
            $activity->setColor($tmp['color'] ?? 'random');
            $activity->setRecordRate($tmp['rate']);
            $activity->setRecordDuration($tmp['duration']);
            $activity->setRecordInternalRate($tmp['internalRate']);
            $activity->setRecordAmount($tmp['count']);
            $model->addActivity($activity);
        }

        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.begin) as year')
            ->addGroupBy('year')
        ;
        foreach ($qb1->getQuery()->getResult() as $year) {
            $tmp = new Year($year['year']);
            $tmp->setTotalRate($year['rate']);
            $tmp->setTotalInternalRate($year['internalRate']);
            $tmp->setTotalDuration($year['duration']);
            $years[$year['year']] = $tmp;

            // fetch grouped by activity and year
            $qb2 = clone $qb;
            $qb2
                ->leftJoin(Activity::class, 'a', Join::WITH, 'a.id = t.activity')
                ->addSelect('a.name as name')
                ->addSelect('a.color as color')
                ->addSelect('YEAR(t.begin) as year')
                ->andWhere('YEAR(t.begin) = :year')
                ->setParameter('year', $year['year'])
                ->addGroupBy('year')
                ->addGroupBy('name')
                ->addGroupBy('color')
            ;
            foreach ($qb2->getQuery()->getResult() as $tmp) {
                $tmp['color'] = $tmp['color'] ?? 'random';
                $model->addYearActivity($tmp['year'], $tmp);
            }
        }
        $model->setYears($years);

        // fetch MONTH for YEAR
        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.begin) as year')
            ->addSelect('MONTH(t.begin) as month')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;
        foreach ($qb1->getQuery()->getResult() as $month) {
            $tmp = new Month($month['month']);
            $tmp->setTotalRate($month['rate']);
            $tmp->setTotalInternalRate($month['internalRate']);
            $tmp->setTotalDuration($month['duration']);
            $model->getYear($month['year'])->setMonth($tmp);
        }

        return $model;
    }

    /**
     * @param ProjectViewQuery $query
     * @return Project[]
     */
    public function findProjectsForView(ProjectViewQuery $query): array
    {
        $user = $query->getUser();
        $today = clone $query->getToday();

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p')
            ->leftJoin('p.customer', 'c')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.end'),
                    $qb->expr()->gte('p.end', ':project_end')
                )
            )
            ->addGroupBy('p')
            ->setParameter('project_end', $today, Types::DATETIME_MUTABLE)
        ;

        if ($query->getCustomer() !== null) {
            $qb->andWhere($qb->expr()->eq('c', ':customer'));
            $qb->setParameter('customer', $query->getCustomer()->getId());
        }

        if (!$query->isIncludeNoWork()) {
            $qb
                ->leftJoin(Timesheet::class, 't', 'WITH', 'p.id = t.project')
                ->andHaving($qb->expr()->gt('SUM(t.duration)', 0))
            ;
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

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Project[] $projects
     * @param DateTime|null $today
     * @return ProjectViewModel[]
     */
    public function getProjectView(User $user, array $projects, ?DateTime $today = null): array
    {
        $factory = DateTimeFactory::createByUser($user);
        if (null === $today) {
            $today = $factory->createDateTime();
        }

        $today = clone $today;

        $begin = $factory->getStartOfWeek($today);
        $end = $factory->getEndOfWeek($today);
        $startMonth = (clone $begin)->modify('first day of this month');
        $endMonth = (clone $begin)->modify('last day of this month');

        $projectViews = [];
        foreach ($projects as $project) {
            $projectViews[$project->getId()] = new ProjectViewModel($project);
        }

        $projectIds = array_keys($projectViews);

        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, COUNT(t.id) as amount, COALESCE(SUM(t.duration), 0) AS duration, COALESCE(SUM(t.rate), 0) AS rate, MAX(t.begin) as lastRecord')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->groupBy('t.project')
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationTotal($row['duration']);
            $projectViews[$row['id']]->setRateTotal($row['rate']);
            $projectViews[$row['id']]->setTimesheetCounter($row['amount']);
            if ($row['lastRecord'] !== null) {
                // might be the wrong timezone
                $projectViews[$row['id']]->setLastRecord($factory->createDateTime($row['lastRecord']));
            }
        }

        // values for today
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, COALESCE(SUM(t.duration), 0) AS duration')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere('DATE(t.begin) = :starting_date')
            ->groupBy('t.project')
            ->setParameter('starting_date', $today->format('Y-m-d'))
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationDay($row['duration'] ?? 0);
        }

        // values for the current week
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id, COALESCE(SUM(t.duration), 0) AS duration')
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
            ->select('IDENTITY(t.project) AS id, COALESCE(SUM(t.duration), 0) AS duration')
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
            ->select('IDENTITY(t.project) AS id, COALESCE(SUM(t.duration), 0) AS duration, COALESCE(SUM(t.rate), 0) AS rate')
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
            ->select('IDENTITY(t.project) AS id, COALESCE(SUM(t.duration), 0) AS duration, COALESCE(SUM(t.rate), 0) AS rate')
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
            ->setParameter('billable', true, Types::BOOLEAN)
            ->setParameter('project', array_values($projectIds))
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setBillableDuration($row['duration']);
            $projectViews[$row['id']]->setBillableRate($row['rate']);
        }

        return array_values($projectViews);
    }
}
