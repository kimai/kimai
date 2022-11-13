<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetInterface;

final class DurationYear extends AbstractCounterYear
{
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'duration',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    public function getData(array $options = []): mixed
    {
        $this->setQuery(TimesheetRepository::STATS_QUERY_DURATION);
        $this->setQueryWithUser(false);

        return parent::getData($options);
    }

    public function getPermissions(): array
    {
        return ['view_other_timesheet'];
    }

    protected function getFinancialYearTitle(): string
    {
        return 'stats.durationFinancialYear';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-duration.html.twig';
    }

    public function getId(): string
    {
        return 'DurationYear';
    }
}
