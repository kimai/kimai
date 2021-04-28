<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Customer;

use App\Entity\Customer;
use App\Event\CustomerStatisticEvent;
use App\Model\CustomerStatistic;
use App\Repository\CustomerRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CustomerStatisticService
{
    private $repository;
    private $dispatcher;

    public function __construct(CustomerRepository $customerRepository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $customerRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getCustomerStatistics(Customer $customer): CustomerStatistic
    {
        $statistic = $this->repository->getCustomerStatistics($customer);
        $event = new CustomerStatisticEvent($customer, $statistic);
        $this->dispatcher->dispatch($event);

        return $statistic;
    }
}
