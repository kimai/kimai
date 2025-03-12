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
use App\Timesheet\DateRangeEnum;
use App\Timesheet\DateTimeFactory;
use App\Widget\WidgetInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class RevenueUser extends AbstractWidget
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly SystemConfiguration $systemConfiguration,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
        $this->setOption('daterange', DateRangeEnum::MONTH->value);
    }

    /**
     * @param array<string, string|bool|int|float> $options
     */
    public function getData(array $options = []): mixed
    {
        $range = \is_string($options['daterange']) ? $options['daterange'] : DateRangeEnum::MONTH->value;
        $factory = DateTimeFactory::createByUser($this->getUser());
        $type = DateRangeEnum::tryFrom($range);
        $type ??= DateRangeEnum::MONTH;

        $data = null;

        if ($type === DateRangeEnum::FINANCIAL) {
            if (null !== ($yearConfig = $this->systemConfiguration->getFinancialYearStart())) {
                $begin = $factory->createStartOfFinancialYear($yearConfig);
                $end = $factory->createEndOfFinancialYear($begin);
                $data = $this->getRevenue($begin, $end);
            } else {
                $type = DateRangeEnum::YEAR;
            }
        }

        if ($data === null) {
            $data = match ($type) {
                DateRangeEnum::TODAY => $this->getRevenue($factory->createStartOfDay(), $factory->createEndOfDay()),
                DateRangeEnum::WEEK => $this->getRevenue($factory->getStartOfWeek(), $factory->getEndOfWeek()),
                DateRangeEnum::YEAR => $this->getRevenue($factory->createStartOfYear(), $factory->createEndOfYear()),
                DateRangeEnum::TOTAL => $this->getRevenue(null, null),
                default => $this->getRevenue($factory->getStartOfMonth(), $factory->getEndOfMonth()),
            };
        }

        return [
            'title' => $this->getDateRangeTitle($type),
            'color' => $this->getDateRangeColor($type),
            'value' => $data
        ];
    }

    /**
     * @return array<float>
     */
    private function getRevenue(?\DateTimeInterface $begin, ?\DateTimeInterface $end): array
    {
        $user = $this->getUser();

        /** @var array<\App\Model\Revenue> $data */
        $data = $this->repository->getRevenue($begin, $end, $user);

        $event = new UserRevenueStatisticEvent($user, $begin, $end);
        foreach ($data as $row) {
            $event->addRevenue($row->getCurrency(), $row->getAmount());
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_SMALL;
    }

    public function getTitle(): string
    {
        return 'stats.userRevenue';
    }

    public function getPermissions(): array
    {
        return ['view_rate_own_timesheet'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-revenue.html.twig';
    }

    public function getId(): string
    {
        return 'RevenueUser';
    }
}
