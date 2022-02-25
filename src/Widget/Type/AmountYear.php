<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Event\RevenueStatisticEvent;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AmountYear extends AbstractCounterYear
{
    private $dispatcher;

    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($repository, $systemConfiguration);
        $this->dispatcher = $dispatcher;
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
        $this->titleYear = 'stats.amountFinancialYear';
        $this->setQuery(TimesheetRepository::STATS_QUERY_RATE);
        $this->setQueryWithUser(false);

        $data = parent::getData($options);

        $event = new RevenueStatisticEvent($this->getBegin(), $this->getEnd());
        if ($data !== null) {
            $event->addRevenue($data);
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }

    public function getId(): string
    {
        return 'amountYear';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-money.html.twig';
    }

    public function getPermissions(): array
    {
        return ['view_all_data'];
    }
}
