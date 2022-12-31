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
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CustomerStatisticService
{
    public function __construct(private TimesheetRepository $timesheetRepository, private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * WARNING: this method does not respect the budget type. Your results will always be wither the "full lifetime data" or the "selected date-range".
     *
     * @param Customer $customer
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return CustomerStatistic
     */
    public function getCustomerStatistics(Customer $customer, ?DateTime $begin = null, ?DateTime $end = null): CustomerStatistic
    {
        $statistics = $this->getBudgetStatistic([$customer], $begin, $end);
        $event = new CustomerStatisticEvent($customer, array_pop($statistics), $begin, $end);
        $this->dispatcher->dispatch($event);

        return $event->getStatistic();
    }

    public function getBudgetStatisticModel(Customer $customer, DateTime $today): CustomerBudgetStatisticModel
    {
        $stats = new CustomerBudgetStatisticModel($customer);
        $stats->setStatisticTotal($this->getCustomerStatistics($customer));

        $begin = null;
        $end = $today;

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
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return array<int, CustomerStatistic>
     */
    private function getBudgetStatistic(array $customers, ?DateTime $begin = null, ?DateTime $end = null): array
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

    private function createStatisticQueryBuilder(array $customers, DateTime $begin = null, ?DateTime $end = null): QueryBuilder
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
