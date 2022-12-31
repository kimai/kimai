<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Activity;

use App\Entity\Activity;
use App\Event\ActivityBudgetStatisticEvent;
use App\Event\ActivityStatisticEvent;
use App\Model\ActivityBudgetStatisticModel;
use App\Model\ActivityStatistic;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ActivityStatisticService
{
    public function __construct(private TimesheetRepository $timesheetRepository, private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * WARNING: this method does not respect the budget type. Your results will always be wither the "full lifetime data" or the "selected date-range".
     *
     * @param Activity $activity
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return ActivityStatistic
     */
    public function getActivityStatistics(Activity $activity, ?DateTime $begin = null, ?DateTime $end = null): ActivityStatistic
    {
        $statistics = $this->getBudgetStatistic([$activity], $begin, $end);
        $event = new ActivityStatisticEvent($activity, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
    }

    public function getBudgetStatisticModel(Activity $activity, DateTime $today): ActivityBudgetStatisticModel
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
     * @param DateTime $today
     * @return ActivityBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForActivities(array $activities, DateTime $today): array
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
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return array<int, ActivityStatistic>
     */
    private function getBudgetStatistic(array $activities, ?DateTime $begin = null, ?DateTime $end = null): array
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

    private function createStatisticQueryBuilder(array $activities, DateTime $begin = null, ?DateTime $end = null): QueryBuilder
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
                ->setParameter('begin', $begin, Types::DATETIME_MUTABLE)
            ;
        }

        if ($end !== null) {
            $qb
                ->andWhere($qb->expr()->lte('t.begin', ':end'))
                ->setParameter('end', $end, Types::DATETIME_MUTABLE)
            ;
        }

        return $qb;
    }
}
