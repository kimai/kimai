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
use App\Repository\ActivityRepository;
use App\Repository\Loader\ProjectLoader;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Timesheet\DateTimeFactory;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ProjectStatisticService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly UserRepository $userRepository
    )
    {
    }

    /**
     * WARNING: this method does not respect the budget type. Your results will always be with the "full lifetime data" or the "selected date-range".
     */
    public function getProjectStatistics(Project $project, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): ProjectStatistic
    {
        $statistics = $this->getBudgetStatistic([$project], $begin, $end);
        $event = new ProjectStatisticEvent($project, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
    }

    /**
     * @return Project[]
     */
    public function findInactiveProjects(ProjectInactiveQuery $query): array
    {
        $user = $query->getUser();
        $lastChange = DateTimeImmutable::createFromInterface($query->getLastChange());
        $now = new DateTimeImmutable('now', $lastChange->getTimezone());

        $qb2 = $this->projectRepository->createQueryBuilder('t1');
        $qb2
            ->select('1')
            ->from(Timesheet::class, 't')
            ->andWhere('p = t.project')
            ->andWhere($qb2->expr()->gte('t.begin', ':begin'))
        ;

        $qb = $this->projectRepository->createQueryBuilder('p');
        $qb
            ->select('p, c')
            ->leftJoin('p.customer', 'c')
            ->andWhere($qb->expr()->eq('p.visible', true))
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->andWhere($qb->expr()->not($qb->expr()->exists($qb2)))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.start'),
                    $qb->expr()->lte('p.start', ':project_start')
                )
            )
            ->setParameter('project_start', $now, Types::DATETIME_IMMUTABLE)
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.end'),
                    $qb->expr()->gte('p.end', ':project_end')
                )
            )
            ->setParameter('project_end', $now, Types::DATETIME_IMMUTABLE)
            ->setParameter('begin', $lastChange, Types::DATETIME_IMMUTABLE)
        ;

        $this->projectRepository->addPermissionCriteria($qb, $user);

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->projectRepository->createQueryBuilder('p')->getEntityManager(), false, false, false);
        $loader->loadResults($projects);

        return $projects;
    }

    /**
     * @return Project[]
     */
    public function findProjectsForDateRange(ProjectDateRangeQuery $query, DateRange $dateRange): array
    {
        $user = $query->getUser();
        $begin = $dateRange->getBegin();
        $end = $dateRange->getEnd();

        $qb = $this->projectRepository->createQueryBuilder('p');
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
            $qb2 = $this->projectRepository->createQueryBuilder('t1');
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
        } elseif (!$query->isBudgetIndependent()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('p.budget', 0.0),
                    $qb->expr()->gt('p.timeBudget', 0)
                )
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

        $this->projectRepository->addPermissionCriteria($qb, $user);

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->projectRepository->createQueryBuilder('p')->getEntityManager(), false, false, false);
        $loader->loadResults($projects);

        return $projects;
    }

    public function getBudgetStatisticModel(Project $project, DateTimeInterface $today): ProjectBudgetStatisticModel
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
     * @return ProjectBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForProjects(array $projects, DateTimeInterface $today): array
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
     * @return ProjectBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForProjectsByDateRange(array $projects, DateTimeInterface $begin, DateTimeInterface $end, ?DateTimeInterface $totalsEnd = null): array
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
     * @return array<int, ProjectStatistic>
     */
    public function getBudgetStatistic(array $projects, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): array
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
            ->addSelect('t.exported as exported')
            ->andWhere($qb->expr()->in('t.project', ':project'))
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->groupBy('id')
            ->addGroupBy('billable')
            ->addGroupBy('exported')
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
                    $statistic->setDurationBillable($statistic->getDurationBillable() + $resultRow['duration']);
                    $statistic->setRateBillable($statistic->getRateBillable() + $resultRow['rate']);
                    $statistic->setInternalRateBillable($statistic->getInternalRateBillable() + $resultRow['internalRate']);
                    $statistic->setCounterBillable($statistic->getCounterBillable() + $resultRow['counter']);
                    if ($resultRow['exported']) {
                        $statistic->setDurationBillableExported($statistic->getDurationBillableExported() + $resultRow['duration']);
                        $statistic->setRateBillableExported($statistic->getRateBillableExported() + $resultRow['rate']);
                    }
                }
                if ($resultRow['exported']) {
                    $statistic->setDurationExported($statistic->getDurationExported() + $resultRow['duration']);
                    $statistic->setRateExported($statistic->getRateExported() + $resultRow['rate']);
                    $statistic->setInternalRateExported($statistic->getInternalRateExported() + $resultRow['internalRate']);
                    $statistic->setCounterExported($statistic->getCounterExported() + $resultRow['counter']);
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

        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('COUNT(t.id) as count')
            ->addSelect('t.billable as billable')
            ->andWhere('t.project = :project')
            ->setParameter('project', $query->getProject())
            ->addGroupBy('billable')
        ;

        // fetch stats grouped by ACTIVITY for all time
        $qb1 = clone $qb;
        $qb1
            ->addSelect('IDENTITY(t.activity) as activity')
            ->addGroupBy('t.activity')
        ;

        /** @var array<string, ActivityStatistic> $activities */
        $activities = [];
        /** @var array{"duration": int, "rate": float, "internalRate": float, "count": int, "billable": bool, "activity": int} $tmp */
        foreach ($qb1->getQuery()->getArrayResult() as $tmp) {
            $activityId = $tmp['activity'];
            if (!\array_key_exists($activityId, $activities)) {
                $activity = new ActivityStatistic();
                $activities[$activityId] = $activity;
            } else {
                $activity = $activities[$activityId];
            }

            $activity->setRate($activity->getRate() + $tmp['rate']);
            $activity->setDuration($activity->getDuration() + $tmp['duration']);
            $activity->setInternalRate($activity->getInternalRate() + $tmp['internalRate']);
            $activity->setCounter($activity->getCounter() + $tmp['count']);

            if ($tmp['billable']) {
                $activity->setDurationBillable($activity->getDurationBillable() + $tmp['duration']);
                $activity->setRateBillable($activity->getRateBillable() + $tmp['rate']);
                $activity->setInternalRateBillable($activity->getInternalRateBillable() + $tmp['internalRate']);
            }
        }

        /** @var array<string, Activity> $activityIdToActivity */
        $activityIdToActivity = [];

        if (\count($activities) > 0) {
            // prepare activities for later use
            $qbActivity = $this->activityRepository->createQueryBuilder('a');
            $qbActivity->select('a')->where($qbActivity->expr()->in('a.id', array_keys($activities)));

            /** @var array<int, Activity> $activityResults */
            $activityResults = $qbActivity->getQuery()->getResult();
            foreach ($activityResults as $item) {
                $activityIdToActivity[$item->getId()] = $item;
            }
        }

        foreach ($activities as $activityId => $activity) {
            $activity->setActivity($activityIdToActivity[$activityId]);
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
            /** @var array<int, UserStatistic> $users */
            $users = [];
            /** @var array<int, User> $userResult */
            $userResult = $qb2->getQuery()->getResult();
            foreach ($userResult as $user) {
                $users[$user->getId()] = new UserStatistic($user);
            }

            foreach ($userMonths as $tmp) {
                $user = $users[$tmp['user']]->getUser();
                $year = $model->getUserYear($tmp['year'], $user);
                if ($year === null) {
                    $year = new Year($tmp['year']);
                    for ($i = 1; $i < 13; $i++) {
                        $year->setMonth(new Month($i));
                    }
                    $model->setUserYear($year, $user);
                }
                $month = $year->getMonth($tmp['month']);
                if ($month === null) {
                    $month = new Month($tmp['month']);
                    $year->setMonth($month);
                }
                $month->setTotalRate($month->getTotalRate() + $tmp['rate']);
                $month->setTotalDuration($month->getTotalDuration() + $tmp['duration']);
                $month->setTotalInternalRate($month->getTotalInternalRate() + $tmp['internalRate']);

                if ($tmp['billable']) {
                    $month->setBillableDuration($month->getBillableDuration() + $tmp['duration']);
                    $month->setBillableRate($month->getBillableRate() + $tmp['rate']);
                }
            }

            foreach ($users as $userId => $statistic) {
                foreach ($model->getYears() as $year) {
                    $statYear = $model->getUserYear($year->getYear(), $statistic->getUser());
                    if ($statYear === null) {
                        continue;
                    }
                    foreach ($statYear->getMonths() as $month) {
                        $statistic->addValuesFromMonth($month);
                    }
                }
            }
        }
        // ---------------------------------------------------

        $years = [];

        // make sure that we have all month between project start and end
        if ($project->getStart() !== null) {
            if ($project->getEnd() !== null) {
                $end = clone $project->getEnd();
            } else {
                $end = clone $query->getToday();
                $end->setDate((int) $end->format('Y'), 12, 31);
            }

            $start = clone $project->getStart();
            $start->setDate((int) $start->format('Y'), (int) $start->format('m'), 1);
            $start->setTime(0, 0, 0);

            while ($start !== false && $start < $end) {
                $year = $start->format('Y');
                if (!\array_key_exists($year, $years)) {
                    $years[$year] = new Year($year);
                }
                $tmp = $years[$year];
                $tmp->setMonth(new Month($start->format('m')));
                $start = $start->modify('+1 month');
            }
        }

        // fetch stats grouped by YEARS
        $qb1 = clone $qb;
        $qb1
            ->addSelect('YEAR(t.date) as year')
            ->addGroupBy('year')
        ;

        foreach ($qb1->getQuery()->getResult() as $year) {
            if (!\array_key_exists($year['year'], $years)) {
                $tmp = new Year($year['year']);
                for ($i = 1; $i < 13; $i++) {
                    $tmp->setMonth(new Month($i));
                }
                $years[$year['year']] = $tmp;
            }
        }

        /** @var array<string, array<string, ActivityStatistic>> $yearActivities */
        $yearActivities = [];
        foreach ($years as $yearName => $yearStat) {
            // fetch yearly stats grouped by ACTIVITY and YEAR
            $qb2 = clone $qb;
            $qb2
                ->addSelect('IDENTITY(t.activity) as activity')
                ->addSelect('YEAR(t.date) as year')
                ->andWhere('YEAR(t.date) = :year')
                ->setParameter('year', $yearName)
                ->addGroupBy('year')
                ->addGroupBy('t.activity')
            ;

            /** @var array<int, array{"duration": int, "rate": float, "internalRate": float, "count": int, "billable": bool, "activity": int, "year": int}> $statsTmp */
            $statsTmp = $qb2->getQuery()->getArrayResult();
            foreach ($statsTmp as $tmp) {
                $activityId = $tmp['activity'];
                if (!\array_key_exists($yearName, $yearActivities)) {
                    $yearActivities[$yearName] = [];
                }
                if (!\array_key_exists($activityId, $yearActivities[$yearName])) {
                    $activity = new ActivityStatistic();
                    $activity->setActivity($activityIdToActivity[$activityId]);
                    $yearActivities[$yearName][$activityId] = $activity;
                } else {
                    $activity = $yearActivities[$yearName][$activityId];
                }
                $activity->setRate($activity->getRate() + $tmp['rate']);
                $activity->setDuration($activity->getDuration() + $tmp['duration']);
                $activity->setInternalRate($activity->getInternalRate() + $tmp['internalRate']);
                $activity->setCounter($activity->getCounter() + $tmp['count']);

                if ($tmp['billable']) {
                    $activity->setDurationBillable($activity->getDurationBillable() + $tmp['duration']);
                    $activity->setRateBillable($activity->getRateBillable() + $tmp['rate']);
                    $activity->setInternalRateBillable($activity->getInternalRateBillable() + $tmp['internalRate']);
                }
            }
        }

        foreach ($yearActivities as $year => $yearlyActivities) {
            foreach ($yearlyActivities as $activityId => $activityStatistic) {
                $model->addYearActivity($year, $activityStatistic);
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
            $tmp = $model->getYear($month['year'])->getMonth($month['month']);
            if ($tmp === null) {
                $tmp = new Month($month['month']);
                $model->getYear($month['year'])->setMonth($tmp);
            }
            $tmp->setTotalRate($tmp->getTotalRate() + $month['rate']);
            $tmp->setTotalInternalRate($tmp->getTotalInternalRate() + $month['internalRate']);
            $tmp->setTotalDuration($tmp->getTotalDuration() + $month['duration']);

            if ($month['billable']) {
                $tmp->setBillableDuration($tmp->getBillableDuration() + $month['duration']);
                $tmp->setBillableRate($tmp->getBillableRate() + $month['rate']);
            }
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

        $qb = $this->projectRepository->createQueryBuilder('p');
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

        if ($query->isIncludeWithBudget()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('p.timeBudget', 0),
                    $qb->expr()->gt('p.budget', 0)
                )
            );
        } elseif ($query->isIncludeWithoutBudget()) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('p.timeBudget', 0),
                    $qb->expr()->eq('p.budget', 0)
                )
            );
        }

        $this->projectRepository->addPermissionCriteria($qb, $user);

        /** @var Project[] $projects */
        $projects = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new ProjectLoader($this->projectRepository->createQueryBuilder('p')->getEntityManager(), false, false, false);
        $loader->loadResults($projects);

        return $projects;
    }

    /**
     * @param Project[] $projects
     * @return ProjectViewModel[]
     */
    public function getProjectView(User $user, array $projects, DateTimeInterface $today): array
    {
        $factory = DateTimeFactory::createByUser($user);
        $today = DateTimeImmutable::createFromInterface($today);

        $startOfWeek = $factory->getStartOfWeek($today);
        $endOfWeek = $factory->getEndOfWeek($today);
        $startMonth = (clone $startOfWeek)->modify('first day of this month');
        $endMonth = (clone $startOfWeek)->modify('last day of this month');

        $projectViews = [];

        $budgetStats = $this->getBudgetStatisticModelForProjects($projects, $today);
        foreach ($budgetStats as $model) {
            $project = $model->getProject();
            $projectViews[$project->getId()] = new ProjectViewModel($model);
        }

        $projectIds = array_keys($projectViews);

        $tplQb = $this->timesheetRepository->createQueryBuilder('t');
        $tplQb
            ->select('IDENTITY(t.project) AS id')
            ->addSelect('COALESCE(SUM(t.duration), 0) AS duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) AS rate')
            ->andWhere($tplQb->expr()->in('t.project', ':project'))
            ->groupBy('t.project')
            ->setParameter('project', $projectIds)
        ;

        // find the most recent timesheet for each project
        $qb = clone $tplQb;
        $qb->addSelect('MAX(t.date) as lastRecord');
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
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

        return array_values($projectViews);
    }
}
