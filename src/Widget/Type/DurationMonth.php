<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

final class DurationMonth extends AbstractCounterDuration
{
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
        return 'DurationMonth';
    }

    public function getTitle(): string
    {
        return 'stats.durationMonth';
    }

    public function getData(array $options = []): mixed
    {
        $this->setQueryWithUser(false);
        $this->setBegin('first day of this month 00:00:00');
        $this->setEnd('last day of this month 23:59:59');

        return parent::getData($options);
    }
}
