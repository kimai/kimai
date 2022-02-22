<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Event\UserRevenueStatisticEvent;
use App\Repository\TimesheetRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractUserAmountPeriod extends SimpleStatisticChart
{
    private $dispatcher;

    public function __construct(TimesheetRepository $repository, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($repository);
        $this->dispatcher = $dispatcher;
    }

    public function getTitle(): string
    {
        return 'stats.' . str_replace('userA', 'a', $this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
            'dataType' => 'money',
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
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
