<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class UserBillableWeek extends AbstractBillablePercent
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_WEEK], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_own_timesheet'];
    }

    public function getId(): string
    {
        return 'userBillableWeek';
    }

    /**
 * @param array<string, string|bool|int|null|array<string, mixed>> $options
 */
    public function getData(array $options = []): mixed
    {
        return parent::getData(array_merge([$this->repository->getDurationForTimeRange($this->createWeekStartDate(), $this->createWeekEndDate(), $this->getUser(), true), $this->repository->getDurationForTimeRange($this->createWeekStartDate(), $this->createWeekEndDate(), $this->getUser())], $options));
    }
}
