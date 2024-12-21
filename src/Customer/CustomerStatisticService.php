<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Customer;

use App\Entity\Customer;
use App\Entity\Project;
use App\Event\CustomerStatisticEvent;
use App\Model\CustomerBudgetStatisticModel;
use App\Model\CustomerStatistic;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CustomerStatisticService
{
    public function __construct(private readonly TimesheetRepository $timesheetRepository, private readonly EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * WARNING: this method does not respect the budget type. Your results will always be with the "full lifetime data" or the "selected date-range".
     */
    public function getCustomerStatistics(Customer $customer, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): CustomerStatistic
    {
        $statistics = $this->getBudgetStatistic([$customer], $begin, $end);
        $event = new CustomerStatisticEvent($customer, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
    }

    public function getBudgetStatisticModel(Customer $customer, DateTimeInterface $today): CustomerBudgetStatisticModel
    {
        $stats = new CustomerBudgetStatisticModel($customer);
        $stats->setStatisticTotal($this->getCustomerStatistics($customer));

        $begin = null;
        $end = DateTimeImmutable::createFromInterface($today);

        if ($customer->isMonthlyBudget()) {
            $dateFactory = new DateTimeFactory($today->getTimezone());
            $begin = $dateFactory->getStartOfMonth($today);
            $end = $dateFactory->getEndOfMonth($today);
        }

        $stats->setStatistic($this->getCustomerStatistics($customer, $begin, $end));

        return $stats;
    }

    /**
     * @param Customer[] $customers
     * @return array<int, CustomerStatistic>
     */
    private function getBudgetStatistic(array $customers, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): array
    {
        $statistics = [];
        foreach ($customers as $customer) {
            $statistics[$customer->getId()] = new CustomerStatistic();
        }

        $qb = $this->createStatisticQueryBuilder($customers, $begin, $end);

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

    private function createStatisticQueryBuilder(array $customers, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): QueryBuilder
    {
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(p.customer) AS id')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
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
            ->andWhere($qb->expr()->in('p.customer', ':customer'))
            ->setParameter('customer', $customers)
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
