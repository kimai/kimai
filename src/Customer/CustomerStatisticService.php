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
use App\Entity\User;
use App\Event\CustomerBudgetStatisticEvent;
use App\Event\CustomerStatisticEvent;
use App\Model\CustomerBudgetStatisticModel;
use App\Model\CustomerStatistic;
use App\Reporting\CustomerView\CustomerViewModel;
use App\Reporting\CustomerView\CustomerViewQuery;
use App\Repository\CustomerRepository;
use App\Repository\Loader\CustomerLoader;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CustomerStatisticService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private TimesheetRepository $timesheetRepository,
        private EventDispatcherInterface $dispatcher
    )
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
            /** @var array{'id': string, 'duration': int, 'internalRate': float, 'counter': int, 'rate': float, 'billable': int, 'exported': int} $resultRow */
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
     * @param array<Customer> $customers
     */
    private function createStatisticQueryBuilder(array $customers, DateTime $begin = null, ?DateTime $end = null): QueryBuilder
    {
        $qb = $this->timesheetRepository->createQueryBuilder('t');
        $qb
            ->select('IDENTITY(p.customer) AS id')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
            ->join(Customer::class, 'c', Query\Expr\Join::WITH, 'p.customer = c.id')
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

    /**
     * @param CustomerViewQuery $query
     * @return Customer[]
     */
    public function findCustomersForView(CustomerViewQuery $query): array
    {
        $user = $query->getUser();

        $qb = $this->customerRepository->createQueryBuilder('c');
        $qb
            ->select('c')
            ->andWhere($qb->expr()->eq('c.visible', true))
            ->addGroupBy('c')
        ;

        if ($query->isIncludeWithBudget()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gt('c.timeBudget', 0),
                    $qb->expr()->gt('c.budget', 0)
                )
            );
        } elseif ($query->isIncludeWithoutBudget()) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('c.timeBudget', 0),
                    $qb->expr()->eq('c.budget', 0)
                )
            );
        }

        $this->customerRepository->addPermissionCriteria($qb, $user);

        /** @var Customer[] $customers */
        $customers = $qb->getQuery()->getResult();

        // pre-cache customer objects instead of joining them
        $loader = new CustomerLoader($this->customerRepository->createQueryBuilder('c')->getEntityManager(), false);
        $loader->loadResults($customers);

        return $customers;
    }

    /**
     * @param User $user
     * @param Customer[] $customers
     * @param DateTime $today
     * @return CustomerViewModel[]
     */
    public function getCustomerView(User $user, array $customers, DateTime $today): array
    {
        $today = clone $today;

        /** @var array<int, CustomerViewModel> $customerViews */
        $customerViews = [];
        foreach ($customers as $customer) {
            $customerViews[$customer->getId()] = new CustomerViewModel($customer);
        }

        $budgetStats = $this->getBudgetStatisticModelForCustomers($customers, $today);
        foreach ($budgetStats as $model) {
            $customerViews[$model->getCustomer()->getId()]->setBudgetStatisticModel($model);
        }

        $customerIds = array_keys($customerViews);

        $tplQb = $this->timesheetRepository->createQueryBuilder('t');
        $tplQb
            ->select('c.id AS id')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
            ->join(Customer::class, 'c', Query\Expr\Join::WITH, 'p.customer = c.id')
            ->addSelect('COUNT(t.id) as amount')
            ->addSelect('COALESCE(SUM(t.duration), 0) AS duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) AS rate')
            ->andWhere($tplQb->expr()->in('c.id', ':customer'))
            ->groupBy('id')
            ->setParameter('customer', array_values($customerIds))
        ;

        $qb = clone $tplQb;

        $result = $qb->getQuery()->getScalarResult();
        /** @var array{'duration': int, 'amount': int, 'rate': float, 'id': string, 'exported': int} $row */
        foreach ($result as $row) {
            $customerViews[$row['id']]->setDurationTotal($row['duration']);
            $customerViews[$row['id']]->setRateTotal($row['rate']);
            $customerViews[$row['id']]->setTimesheetCounter($row['amount']);
        }

        $qb = clone $tplQb;
        $qb
            ->addSelect('t.exported')
            ->addSelect('t.billable')
            ->addGroupBy('t.exported')
            ->addGroupBy('t.billable')
        ;
        $result = $qb->getQuery()->getScalarResult();
        /** @var array{'duration': int, 'billable': int, 'rate': float, 'exported': int, 'id': string} $row */
        foreach ($result as $row) {
            $view = $customerViews[$row['id']];
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

        return array_values($customerViews);
    }

    /**
     * @param Customer[] $customers
     * @param DateTime $today
     * @return CustomerBudgetStatisticModel[]
     */
    public function getBudgetStatisticModelForCustomers(array $customers, DateTime $today): array
    {
        $models = [];
        $monthly = [];
        $allTime = [];

        foreach ($customers as $customer) {
            $models[$customer->getId()] = new CustomerBudgetStatisticModel($customer);
            if ($customer->isMonthlyBudget()) {
                $monthly[] = $customer;
            } else {
                $allTime[] = $customer;
            }
        }

        $statisticsTotal = $this->getBudgetStatistic($customers);
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

        $event = new CustomerBudgetStatisticEvent($models, $begin, $end);
        $this->dispatcher->dispatch($event);

        return $models;
    }
}
