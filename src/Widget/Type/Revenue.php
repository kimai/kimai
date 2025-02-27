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
use App\Timesheet\DateTimeFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

final class Revenue extends AbstractWidget
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly SystemConfiguration $systemConfiguration,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $financialYear = null;
        $factory = DateTimeFactory::createByUser($this->getUser());

        if (null !== ($yearConfig = $this->systemConfiguration->getFinancialYearStart())) {
            $begin = $factory->createStartOfFinancialYear($yearConfig);
            $end = $factory->createEndOfFinancialYear($begin);
            $financialYear = $this->getRevenue($begin, $end);
        }

        return [
            'today' => $this->getRevenue($factory->createStartOfDay(), $factory->createEndOfDay()),
            'week' => $this->getRevenue($factory->getStartOfWeek(), $factory->getEndOfWeek()),
            'month' => $this->getRevenue($factory->getStartOfMonth(), $factory->getEndOfMonth()),
            'year' => $this->getRevenue($factory->createStartOfYear(), $factory->createEndOfYear()),
            'total' => $this->getRevenue(null, null),
            'financial' => $financialYear,
        ];
    }

    private function getRevenue(?\DateTimeInterface $begin, ?\DateTimeInterface $end): array
    {
        /** @var array<\App\Model\Revenue> $data */
        $data = $this->repository->getRevenue($begin, $end);

        $event = new RevenueStatisticEvent($begin, $end);
        foreach ($data as $row) {
            $event->addRevenue($row->getCurrency(), $row->getAmount());
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }

    public function getTitle(): string
    {
        return 'revenue';
    }

    public function getPermissions(): array
    {
        return ['view_all_data'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-revenue.html.twig';
    }

    public function getId(): string
    {
        return 'Revenue';
    }
}
