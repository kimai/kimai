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

final class ActiveTimesheets extends AbstractSimpleStatisticChart
{
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_TOTAL, 'icon' => 'duration'], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_all_data'];
    }

    public function getId(): string
    {
        return 'activeRecordings';
    }

    public function getTitle(): string
    {
        return 'stats.activeRecordings';
    }

    public function getData(array $options = []): mixed
    {
        $this->setQueryWithUser(false);
        $this->setQuery(TimesheetRepository::STATS_QUERY_ACTIVE);

        return parent::getData($options);
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
