<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Activity;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\ActivityBudgetStatisticEvent;
use App\Event\ActivityStatisticEvent;
use App\Model\ActivityBudgetStatisticModel;
use App\Model\ActivityStatistic;
use App\Reporting\ActivityView\ActivityViewModel;
use App\Reporting\ActivityView\ActivityViewQuery;
use App\Repository\ActivityRepository;
use App\Repository\Loader\ActivityLoader;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ActivityStatisticService
{
    public function __construct(
        private readonly TimesheetRepository $timesheetRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
    }

    /**
     * WARNING: this method does not respect the budget type.
     * Your results will always be with the "full lifetime data" or the "selected date-range".
     */
    public function getActivityStatistics(Activity $activity, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): ActivityStatistic
    {
        $statistics = $this->getBudgetStatistic([$activity], $begin, $end);
        $event = new ActivityStatisticEvent($activity, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
    }

    public function getBudgetStatisticModel(Activity $activity, DateTimeInterface $today): ActivityBudgetStatisticModel
    {
        $stats = new ActivityBudgetStatisticModel($activity);
        $stats->setStatisticTotal($this->getActivityStatistics($activity));

        $begin = null;
        $end = $today;

        if ($activity->isMonthlyBudget()) {
            $dateFactory = new DateTimeFactory($today->getTimezone());
            $begin = $dateFactory->getStartOfMonth($today);
            $end = $dateFactory->getEndOfMonth($today);
        }

        $stats->setStatistic($this->getActivityStatistics($activity, $begin, $end));

        return $stats;
    }

    /**
     * @param Activity[] $activities
     * @return ActivityBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForActivities(array $activities, DateTimeInterface $today): array
    {
        $models = [];
        $monthly = [];
        $allTime = [];

        foreach ($activities as $activity) {
            $models[$activity->getId()] = new ActivityBudgetStatisticModel($activity);
            if ($activity->isMonthlyBudget()) {
                $monthly[] = $activity;
            } else {
                $allTime[] = $activity;
            }
        }

        $statisticsTotal = $this->getBudgetStatistic($activities);
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

        $event = new ActivityBudgetStatisticEvent($models, $begin, $end);
        $this->dispatcher->dispatch($event);

        return $models;
    }

    /**
     * @param Activity[] $activities
     * @return array<int|string, ActivityStatistic>
     */
    private function getBudgetStatistic(array $activities, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): array
    {
        $statistics = [];
        foreach ($activities as $activity) {
            $statistics[$activity->getId()] = new ActivityStatistic();
        }

        $qb = $this->createStatisticQueryBuilder($activities, $begin, $end);

        $result = $qb->getQuery()->getResult();

        if (null !== $result) {
            foreach ($result as $resultRow) {
                $statistic = $statistics[$resultRow['id']];
                $statistic->addDuration((int) $resultRow['duration']);
                $statistic->addRate((float) $resultRow['rate']);
                $statistic->addInternalRate((float) $resultRow['internalRate']);
                $statistic->addCounter((int) $resultRow['counter']);
                if ($resultRow['billable']) {
                    $statistic->addDurationBillable((int) $resultRow['duration']);
                    $statistic->addRateBillable((float) $resultRow['rate']);
                    $statistic->addInternalRateBillable((float) $resultRow['internalRate']);
                    $statistic->addCounterBillable((int) $resultRow['counter']);
                    if ($resultRow['exported']) {
                        $statistic->addDurationBillableExported((int) $resultRow['duration']);
                        $statistic->addRateBillableExported((float) $resultRow['rate']);
                    }
                }
                if ($resultRow['exported']) {
                    $statistic->addDurationExported((int) $resultRow['duration']);
                    $statistic->addRateExported((float) $resultRow['rate']);
                    $statistic->addInternalRateExported((float) $resultRow['internalRate']);
                    $statistic->addCounterExported((int) $resultRow['counter']);
                }
            }
        }

        return $statistics;
    }

    /**
     * @param Activity[] $activities
     */
    private function createStatisticQueryBuilder(array $activities, \DateTimeInterface $begin = null, ?\DateTimeInterface $end = null): QueryBuilder
    {
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(t.activity) AS id')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('COUNT(t.id) as counter')
            ->addSelect('t.billable as billable')
            ->addSelect('t.exported as exported')
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->groupBy('id')
            ->addGroupBy('billable')
            ->addGroupBy('exported')
            ->andWhere($qb->expr()->in('t.activity', ':activity'))
            ->setParameter('activity', $activities)
        ;

        if ($begin !== null) {
            $qb
                ->andWhere($qb->expr()->gte('t.begin', ':begin'))
                ->setParameter('begin', DateTimeImmutable::createFromInterface($begin), Types::DATETIME_IMMUTABLE)
            ;
        }

        if ($end !== null) {
            $qb
                ->andWhere($qb->expr()->lte('t.begin', ':end'))
                ->setParameter('end', DateTimeImmutable::createFromInterface($end), Types::DATETIME_IMMUTABLE)
            ;
        }

        return $qb;
    }

    /**
     * @return Activity[]
     */
    public function findActivitiesForView(ActivityViewQuery $query): array
    {
        $user = $query->getUser();
        $today = clone $query->getToday();

        $qb = $this->activityRepository->createQueryBuilder('a');
        $qb
            ->select('a')
            ->leftJoin('a.project', 'p')
            ->leftJoin('p.customer', 'c')
            ->andWhere($qb->expr()->eq('a.visible', true))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.project'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('p.visible', true),
                        $qb->expr()->eq('c.visible', true),
                        $qb->expr()->orX(
                            $qb->expr()->isNull('p.end'),
                            $qb->expr()->gte('p.end', ':project_end')
                        )
                    )
                )
            )
            ->addGroupBy('a')
            ->setParameter('project_end', $today, Types::DATETIME_MUTABLE)
        ;

        if ($query->getProject() !== null) {
            $qb->andWhere($qb->expr()->eq('p', ':project'));
            $qb->setParameter('project', $query->getProject()->getId());
        }

        if (!$query->isIncludeNoWork()) {
            $qb
                ->leftJoin(Timesheet::class, 't', 'WITH', 'a.id = t.activity')
                ->andHaving($qb->expr()->gt('SUM(t.duration)', 0))
            ;
        }

        if ($query->isIncludeWithBudget()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('a.timeBudget', 0),
                    $qb->expr()->gt('a.budget', 0)
                )
            );
        } elseif ($query->isIncludeWithoutBudget()) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.timeBudget', 0),
                    $qb->expr()->eq('a.budget', 0)
                )
            );
        }

        $this->activityRepository->addPermissionCriteria($qb, $user);

        /** @var Activity[] $activities */
        $activities = $qb->getQuery()->getResult();

        // pre-cache project objects instead of joining them
        $loader = new ActivityLoader($this->activityRepository->createQueryBuilder('a')->getEntityManager());
        $loader->loadResults($activities);

        return $activities;
    }

    /**
     * @param Activity[] $activities
     * @return ActivityViewModel[]
     */
    public function getActivityView(User $user, array $activities, DateTimeInterface $today): array
    {
        $factory = DateTimeFactory::createByUser($user);
        $today = clone $today;

        $startOfWeek = $factory->getStartOfWeek($today);
        $endOfWeek = $factory->getEndOfWeek($today);
        $startMonth = (clone $startOfWeek)->modify('first day of this month');
        $endMonth = (clone $startOfWeek)->modify('last day of this month');

        $activityView = [];
        foreach ($activities as $activity) {
            $activityView[$activity->getId()] = new ActivityViewModel($activity);
        }

        $budgetStats = $this->getBudgetStatisticModelForActivities($activities, $today);
        foreach ($budgetStats as $model) {
            $activityView[$model->getActivity()->getId()]->setBudgetStatisticModel($model);
        }

        $activityIds = array_keys($activityView);

        $tplQb = $this->timesheetRepository->createQueryBuilder('t');
        $tplQb
            ->select('IDENTITY(t.activity) AS id')
            ->addSelect('COUNT(t.id) as amount')
            ->addSelect('COALESCE(SUM(t.duration), 0) AS duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) AS rate')
            ->andWhere($tplQb->expr()->in('t.activity', ':activity'))
            ->groupBy('t.activity')
            ->setParameter('activity', $activityIds)
        ;

        $qb = clone $tplQb;
        $qb->addSelect('MAX(t.date) as lastRecord');

        /** @var array<int, array{id: int, amount: int, duration: int, rate: float, lastRecord: string}> $result */
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $activityView[$row['id']]->setDurationTotal($row['duration']);
            $activityView[$row['id']]->setRateTotal($row['rate']);
            $activityView[$row['id']]->setTimesheetCounter($row['amount']);
            if ($row['lastRecord'] !== null) {
                // might be the wrong timezone
                $activityView[$row['id']]->setLastRecord($factory->createDateTime($row['lastRecord']));
            }
        }

        // values for today
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) = :start_date')
            ->setParameter('start_date', $today, Types::DATETIME_MUTABLE)
        ;

        /** @var array<int, array{id: int, amount: int, duration: int, rate: float}> $result */
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $activityView[$row['id']]->setDurationDay($row['duration'] ?? 0);
        }

        // values for the current week
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) BETWEEN :start_date AND :end_date')
            ->setParameter('start_date', $startOfWeek, Types::DATETIME_MUTABLE)
            ->setParameter('end_date', $endOfWeek, Types::DATETIME_MUTABLE)
        ;

        /** @var array<int, array{id: int, amount: int, duration: int, rate: float}> $result */
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $activityView[$row['id']]->setDurationWeek($row['duration']);
        }

        // values for the current month
        $qb = clone $tplQb;
        $qb
            ->andWhere('DATE(t.date) BETWEEN :start_date AND :end_date')
            ->setParameter('start_date', $startMonth, Types::DATETIME_MUTABLE)
            ->setParameter('end_date', $endMonth, Types::DATETIME_MUTABLE)
        ;

        /** @var array<int, array{id: int, amount: int, duration: int, rate: float}> $result */
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            $activityView[$row['id']]->setDurationMonth($row['duration']);
        }

        $qb = clone $tplQb;
        $qb
            ->addSelect('t.exported')
            ->addSelect('t.billable')
            ->addGroupBy('t.exported')
            ->addGroupBy('t.billable')
        ;

        /** @var array<int, array{id: int, amount: int, duration: int, rate: float, exported: bool, billable: bool}> $result */
        $result = $qb->getQuery()->getScalarResult();
        foreach ($result as $row) {
            /** @var ActivityViewModel $view */
            $view = $activityView[$row['id']];
            if ($row['billable'] === 1 && $row['exported'] === 1) {
                $view->setBillableDuration($view->getBillableDuration() + $row['duration']);
                $view->setBillableRate($view->getBillableRate() + $row['rate']);
            } elseif ($row['billable'] === 1 && $row['exported'] === 0) {
                $view->setBillableDuration($view->getBillableDuration() + $row['duration']);
                $view->setBillableRate($view->getBillableRate() + $row['rate']);
                $view->setNotExportedDuration($view->getNotExportedDuration() + $row['duration']);
                $view->setNotExportedRate($view->getNotExportedRate() + $row['rate']);
                $view->setNotBilledDuration($view->getNotBilledDuration() + $row['duration']);
                $view->setNotBilledRate($view->getNotBilledRate() + $row['rate']);
            } elseif ($row['billable'] === 0 && $row['exported'] === 0) {
                $view->setNotExportedDuration($view->getNotExportedDuration() + $row['duration']);
                $view->setNotExportedRate($view->getNotExportedRate() + $row['rate']);
            }
            // the last possible case $row['billable'] === 0 && $row['exported'] === 1 is extremely unlikely and not used
        }

        return array_values($activityView);
    }
}
