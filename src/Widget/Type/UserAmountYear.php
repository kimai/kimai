<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Event\UserRevenueStatisticEvent;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserAmountYear extends CounterYear
{
    private $dispatcher;

    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($repository, $systemConfiguration);
        $this->dispatcher = $dispatcher;
        $this->setId('userAmountYear');
        $this->setOption('dataType', 'money');
        $this->setOption('icon', 'money');
        $this->setOption('color', WidgetInterface::COLOR_YEAR);
        $this->setTitle('stats.amountYear');
    }

    public function getData(array $options = [])
    {
        $this->titleYear = 'stats.amountFinancialYear';
        $this->setQuery(TimesheetRepository::STATS_QUERY_RATE);
        $this->setQueryWithUser(true);

        $data = parent::getData($options);

        $event = new UserRevenueStatisticEvent($this->user, $this->begin, $this->end);
        if ($data !== null) {
            $event->addRevenue($data);
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }
}
