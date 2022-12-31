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
use App\Model\Revenue;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AmountYear extends AbstractCounterYear
{
    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration, private EventDispatcherInterface $dispatcher)
    {
        parent::__construct($repository, $systemConfiguration);
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    public function getData(array $options = []): mixed
    {
        $this->setQuery(TimesheetRepository::STATS_QUERY_RATE);
        $this->setQueryWithUser(false);

        /** @var array<Revenue> $data */
        $data = parent::getData($options);

        $event = new RevenueStatisticEvent($this->getBegin(), $this->getEnd());
        foreach ($data as $row) {
            $event->addRevenue($row->getCurrency(), $row->getAmount());
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }

    public function getId(): string
    {
        return 'AmountYear';
    }

    protected function getFinancialYearTitle(): string
    {
        return 'stats.amountFinancialYear';
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
