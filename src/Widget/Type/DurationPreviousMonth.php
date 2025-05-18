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

final class DurationPreviousMonth extends AbstractCounterDuration
{
    public function __construct(private readonly TimesheetRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_MONTH], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_other_timesheet'];
    }

    public function getId(): string
    {
        return 'DurationPreviousMonth';
    }

    public function getTitle(): string
    {
        return 'stats.durationPreviousMonth';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): int
    {
        try {
            return $this->repository->getDurationForTimeRange($this->createPreviousMonthStartDate(), $this->createPreviousMonthEndDate(), null);
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
