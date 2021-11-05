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
use App\Event\ProjectBudgetStatisticEvent;
use App\Event\ProjectStatisticEvent;
use App\Form\Model\DateRange;
use App\Model\ActivityStatistic;
use App\Model\ProjectBudgetStatisticModel;
use App\Model\ProjectStatistic;
use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use App\Model\UserStatistic;
use App\Reporting\ProjectDateRange\ProjectDateRangeQuery;
use App\Reporting\ProjectDetails\ProjectDetailsModel;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use App\Reporting\ProjectInactive\ProjectInactiveQuery;
use App\Reporting\ProjectView\ProjectViewModel;
use App\Reporting\ProjectView\ProjectViewQuery;
use App\Repository\Loader\ProjectLoader;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
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
    private $userRepository;

    public function __construct(ProjectRepository $projectRepository, TimesheetRepository $timesheetRepository, EventDispatcherInterface $dispatcher, UserRepository $userRepository)
    {
        $this->repository = $projectRepository;
        $this->timesheetRepository = $timesheetRepository;
        $this->dispatcher = $dispatcher;
        $this->userRepository = $userRepository;
    }

    /**
     * WARNING: this method does not respect the budget type. Your results will always be wither the "full lifetime data" or the "selected date-range".
     *
     * @param Project $project
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return ProjectStatistic
     */
    public function getProjectStatistics(Project $project, ?DateTime $begin = null, ?DateTime $end = null): ProjectStatistic
    {
        $statistics = $this->getBudgetStatistic([$project], $begin, $end);
        $event = new ProjectStatisticEvent($project, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
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

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->repository->createQueryBuilder('p')->getEntityManager());
        $loader->loadResults($projects);

        return $projects;
    }

    /**
     * @param ProjectDateRangeQuery $query
     * @return Project[]
     */
    public function findProjectsForDateRange(ProjectDateRangeQuery $query, DateRange $dateRange): array
    {
        $user = $query->getUser();
        $begin = $dateRange->getBegin();
        $end = $dateRange->getEnd();

        $qb = $this->repository->createQueryBuilder('p');
        $qb
            ->select('p')
            ->leftJoin('p.customer', 'c')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->lte('p.start', ':end'),
                        $qb->expr()->isNull('p.start')
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->gte('p.end', ':begin'),
                        $qb->expr()->isNull('p.end')
                    )
                )
            )
            ->setParameter('begin', $begin, Types::DATETIME_MUTABLE)
            ->setParameter('end', $end, Types::DATETIME_MUTABLE)
        ;

        if (!$query->isIncludeNoWork()) {
            $qb2 = $this->repository->createQueryBuilder('t1');
            $qb2
                ->select('1')
                ->from(Timesheet::class, 't')
                ->andWhere('p = t.project')
                ->andWhere($qb2->expr()->between('t.begin', ':begin', ':end'))
            ;
            $qb->andWhere($qb->expr()->exists($qb2));
        }

        if ($query->isIncludeNoBudget()) {
            $qb->andWhere(
                $qb->expr()->eq('p.budget', 0.0),
                $qb->expr()->eq('p.timeBudget', 0)
            );
        } else {
            $qb->andWhere(
                $qb->expr()->gt('p.budget', 0.0),
                $qb->expr()->gt('p.timeBudget', 0)
            );
            if ($query->isBudgetTypeMonthly()) {
                $qb->andWhere(
                    $qb->expr()->eq('p.budgetType', ':typeMonth')
                );
                $qb->setParameter('typeMonth', 'month');
            } else {
                $qb->andWhere(
                    $qb->expr()->isNull('p.budgetType')
                );
            }
        }

        if ($query->getCustomer() !== null) {
            $qb->andWhere($qb->expr()->eq('p.customer', ':customer'))
                ->setParameter('customer', $query->getCustomer());
        }

        $this->repository->addPermissionCriteria($qb, $user);

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->repository->createQueryBuilder('p')->getEntityManager());
        $loader->loadResults($projects);

        return $projects;
    }

    public function getBudgetStatisticModel(Project $project, DateTime $today): ProjectBudgetStatisticModel
    {
        $stats = new ProjectBudgetStatisticModel($project);
        $stats->setStatisticTotal($this->getProjectStatistics($project));

        $begin = null;
        $end = $today;

        if ($project->isMonthlyBudget()) {
            $dateFactory = new DateTimeFactory($today->getTimezone());
            $begin = $dateFactory->getStartOfMonth($today);
            $end = $dateFactory->getEndOfMonth($today);
        }

        $stats->setStatistic($this->getProjectStatistics($project, $begin, $end));

        return $stats;
    }

    /**
     * @param Project[] $projects
     * @param DateTime $today
     * @return ProjectBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForProjects(array $projects, DateTime $today): array
    {
        $models = [];
        $monthly = [];
        $allTime = [];

        foreach ($projects as $project) {
            $models[$project->getId()] = new ProjectBudgetStatisticModel($project);
            if ($project->isMonthlyBudget()) {
                $monthly[] = $project;
            } else {
                $allTime[] = $project;
            }
        }

        $statisticsTotal = $this->getBudgetStatistic($projects);
        foreach ($statisticsTotal as $id => $statistic) {
            $models[$id]->setStatisticTotal($statistic);
        }

        $dateFactory = new DateTimeFactory($today->getTimezone());

        $begin = null;
        $end = $today;

        if (\count($monthly) > 0) {
            $begin = $dateFactory->getStartOfMonth($today);
            $end = $dateFactory->getEndOfMonth($today);
            $statistics = $this->getBudgetStatistic($monthly, $begin, $end);
            foreach ($statistics as $id => $statistic) {
                $models[$id]->setStatistic($statistic);
            }
        }

        if (\count($allTime) > 0) {
            // display the budget at the end of the selected period and not the total sum of all times (do not include times in the future)
            $statistics = $this->getBudgetStatistic($allTime, null, $today);
            foreach ($statistics as $id => $statistic) {
                $models[$id]->setStatistic($statistic);
            }
        }

        $event = new ProjectBudgetStatisticEvent($models, $begin, $end);
        $this->dispatcher->dispatch($event);

        return $models;
    }

    /**
     * @param Project[] $projects
     * @param DateTime $begin
     * @param DateTime $end
     * @param DateTime|null $totalsEnd
     * @return ProjectBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForProjectsByDateRange(array $projects, DateTime $begin, DateTime $end, ?DateTime $totalsEnd = null): array
    {
        $models = [];

        foreach ($projects as $project) {
            $models[$project->getId()] = new ProjectBudgetStatisticModel($project);
        }

        $statisticsTotal = $this->getBudgetStatistic($projects, null, $totalsEnd);
        foreach ($statisticsTotal as $projectId => $statistic) {
            $models[$projectId]->setStatisticTotal($statistic);
        }

        $statistics = $this->getBudgetStatistic($projects, $begin, $end);
        foreach ($statistics as $projectId => $statistic) {
            $models[$projectId]->setStatistic($statistic);
        }

        $event = new ProjectBudgetStatisticEvent($models, $begin, $end);
        $this->dispatcher->dispatch($event);

        return $models;
    }

    /**
     * @param Project[] $projects
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return array<int, ProjectStatistic>
     */
    public function getBudgetStatistic(array $projects, ?DateTime $begin = null, ?DateTime $end = null): array
    {
        $statistics = [];
        foreach ($projects as $project) {
            $statistics[$project->getId()] = new ProjectStatistic();
        }

        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.project) AS id')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('COUNT(t.id) as counter')
            ->addSelect('t.billable as billable')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->groupBy('id')
            ->addGroupBy('billable')
            ->setParameter('project', array_keys($statistics))
        ;

        if ($begin !== null) {
            $qb
                ->andWhere($qb->expr()->gte('t.begin', ':begin'))
                ->setParameter('begin', $begin, Types::DATETIME_MUTABLE)
            ;
        }

        if ($end !== null) {
            $qb
                ->andWhere($qb->expr()->lte('t.begin', ':end'))
                ->setParameter('end', $end, Types::DATETIME_MUTABLE)
            ;
        }

        $result = $qb->getQuery()->getResult();

        if (null !== $result) {
            foreach ($result as $resultRow) {
                $statistic = $statistics[$resultRow['id']];
                $statistic->setDuration($statistic->getDuration() + $resultRow['duration']);
                $statistic->setRate($statistic->getRate() + $resultRow['rate']);
                $statistic->setInternalRate($statistic->getInternalRate() + $resultRow['internalRate']);
                $statistic->setCounter($statistic->getCounter() + $resultRow['counter']);
                if ($resultRow['billable']) {
                    $statistic->setDurationBillable($resultRow['duration']);
                    $statistic->setRateBillable($resultRow['rate']);
                    $statistic->setInternalRateBillable($resultRow['internalRate']);
                    $statistic->setCounterBillable($resultRow['counter']);
                }
            }
        }

        return $statistics;
    }

    /**
     * @param ProjectDetailsQuery $query
     * @return ProjectDetailsModel
     */
    public function getProjectsDetails(ProjectDetailsQuery $query): ProjectDetailsModel
    {
        $project = $query->getProject();
        $model = new ProjectDetailsModel($project);
        $model->setBudgetStatisticModel($this->getBudgetStatisticModel($project, $query->getToday()));

        $years = [];
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('COUNT(t.id) as count')
            ->andWhere('t.project = :project')
            ->setParameter('project', $query->getProject())
        ;

        // fetch stats grouped by ACTIVITY for all time
        $qb1 = clone $qb;
        $qb1
            ->leftJoin(Activity::class, 'a', Join::WITH, 'a.id = t.activity')
            ->addSelect('a as activity')
            ->addGroupBy('a')
        ;
        foreach ($qb1->getQuery()->getResult() as $tmp) {
            $activity = new ActivityStatistic();
            $activity->setActivity($tmp['activity']);
            $activity->setRecordRate($tmp['rate']);
            $activity->setRecordDuration($tmp['duration']);
            $activity->setRecordInternalRate($tmp['internalRate']);
            $activity->setCounter($tmp['count']);
            $model->addActivity($activity);
        }
        // ---------------------------------------------------

        // fetch stats grouped by YEAR, MONTH and USER
        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.date) as year')
            ->addSelect('MONTH(t.date) as month')
            ->addSelect('IDENTITY(t.user) as user')
            ->addGroupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('user')
        ;

        $userMonths = $qb1->getQuery()->getResult();
        $userIds = array_unique(array_column($userMonths, 'user'));

        if (!empty($userIds)) {
            $qb2 = $this->userRepository->createQueryBuilder('u');
            $qb2->select('u')->where($qb2->expr()->in('u.id', $userIds));
            $users = [];
            foreach ($qb2->getQuery()->getResult() as $user) {
                $users[$user->getId()] = new UserStatistic($user);
            }

            foreach ($userMonths as $tmp) {
                $user = $users[$tmp['user']]->getUser();
                $year = $model->getUserYear($tmp['year'], $user);
                if ($year === null) {
                    $year = new Year($tmp['year']);
                    $model->setUserYear($year, $user);
                }
                $month = new Month($tmp['month']);
                $month->setTotalRate($tmp['rate']);
                $month->setTotalDuration($tmp['duration']);
                $month->setTotalInternalRate($tmp['internalRate']);
                $year->setMonth($month);
                $users[$tmp['user']]->addValuesFromMonth($month);
            }
        }
        // ---------------------------------------------------

        // fetch stats grouped by YEARS
        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.date) as year')
            ->addGroupBy('year')
        ;
        foreach ($qb1->getQuery()->getResult() as $year) {
            $tmp = new Year($year['year']);
            $tmp->setTotalRate($year['rate']);
            $tmp->setTotalInternalRate($year['internalRate']);
            $tmp->setTotalDuration($year['duration']);
            $years[$year['year']] = $tmp;

            // fetch yearly stats grouped by ACTIVITY and YEAR
            $qb2 = clone $qb;
            $qb2
                ->leftJoin(Activity::class, 'a', Join::WITH, 'a.id = t.activity')
                ->addSelect('a as activity')
                ->addSelect('YEAR(t.date) as year')
                ->andWhere('YEAR(t.date) = :year')
                ->setParameter('year', $year['year'])
                ->addGroupBy('year')
                ->addGroupBy('a')
            ;
            foreach ($qb2->getQuery()->getResult() as $tmp) {
                $activity = new ActivityStatistic();
                $activity->setActivity($tmp['activity']);
                $activity->setRecordRate($tmp['rate']);
                $activity->setRecordDuration($tmp['duration']);
                $activity->setRecordInternalRate($tmp['internalRate']);
                $activity->setCounter($tmp['count']);
                $model->addYearActivity($tmp['year'], $activity);
            }
        }
        $model->setYears(array_values($years));
        // ---------------------------------------------------

        // fetch stats grouped by MONTH and YEAR
        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.date) as year')
            ->addSelect('MONTH(t.date) as month')
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
        // ---------------------------------------------------

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

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->repository->createQueryBuilder('p')->getEntityManager());
        $loader->loadResults($projects);

        return $projects;
    }

    /**
     * @param User $user
     * @param Project[] $projects
     * @param DateTime $today
     * @return ProjectViewModel[]
     */
    public function getProjectView(User $user, array $projects, DateTime $today): array
    {
        $factory = DateTimeFactory::createByUser($user);
        $today = clone $today;

        $startOfWeek = $factory->getStartOfWeek($today);
        $endOfWeek = $factory->getEndOfWeek($today);
        $startMonth = (clone $startOfWeek)->modify('first day of this month');
        $endMonth = (clone $startOfWeek)->modify('last day of this month');

        $projectViews = [];
        foreach ($projects as $project) {
            $projectViews[$project->getId()] = new ProjectViewModel($project);
        }

        $budgetStats = $this->getBudgetStatisticModelForProjects($projects, $today);
        foreach ($budgetStats as $model) {
            $projectViews[$model->getProject()->getId()]->setBudgetStatisticModel($model);
        }

        $projectIds = array_keys($projectViews);

        $tplQb = $this->timesheetRepository->createQueryBuilder('t');
        $tplQb
            ->select('IDENTITY(t.project) AS id')
            ->addSelect('COUNT(t.id) as amount')
            ->addSelect('COALESCE(SUM(t.duration), 0) AS duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) AS rate')
            ->andWhere($tplQb->expr()->in('t.project', ':project'))
            ->groupBy('t.project')
            ->setParameter('project', array_values($projectIds))
        ;

        $qb = clone $tplQb;
        $qb->addSelect('MAX(t.date) as lastRecord');

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
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) = :start_date')
            ->setParameter('start_date', $today, Types::DATETIME_MUTABLE)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationDay($row['duration'] ?? 0);
        }

        // values for the current week
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) BETWEEN :start_date AND :end_date')
            ->setParameter('start_date', $startOfWeek, Types::DATETIME_MUTABLE)
            ->setParameter('end_date', $endOfWeek, Types::DATETIME_MUTABLE)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationWeek($row['duration']);
        }

        // values for the current month
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) BETWEEN :start_date AND :end_date')
            ->setParameter('start_date', $startMonth, Types::DATETIME_MUTABLE)
            ->setParameter('end_date', $endMonth, Types::DATETIME_MUTABLE)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setDurationMonth($row['duration']);
        }

        // values for all time (not exported)
        $qb = clone $tplQb;
        $qb
            ->andWhere('t.exported = :exported')
            ->setParameter('exported', false, Types::BOOLEAN)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setNotExportedDuration($row['duration']);
            $projectViews[$row['id']]->setNotExportedRate($row['rate']);
        }

        // values for all time (not exported and billable)
        $qb = clone $tplQb;
        $qb
            ->andWhere('t.exported = :exported')
            ->andWhere('t.billable = :billable')
            ->setParameter('exported', false, Types::BOOLEAN)
            ->setParameter('billable', true, Types::BOOLEAN)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setNotBilledDuration($row['duration']);
            $projectViews[$row['id']]->setNotBilledRate($row['rate']);
        }

        // values for all time (none billable)
        $qb = clone $tplQb;
        $qb
            ->andWhere('t.billable = :billable')
            ->groupBy('t.project')
            ->setParameter('billable', true, Types::BOOLEAN)
        ;

        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $projectViews[$row['id']]->setBillableDuration($row['duration']);
            $projectViews[$row['id']]->setBillableRate($row['rate']);
        }

        return array_values($projectViews);
    }
}
