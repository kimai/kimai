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
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ActivityStatisticService
{
    public function __construct(private readonly TimesheetRepository $timesheetRepository, private readonly EventDispatcherInterface $dispatcher)
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
     * @return array<int, ActivityStatistic>
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
}
