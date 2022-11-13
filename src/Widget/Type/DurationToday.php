<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

final class DurationToday extends AbstractCounterDuration
{
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_TODAY], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_other_timesheet'];
    }

    public function getId(): string
    {
        return 'DurationToday';
    }

    public function getTitle(): string
    {
        return 'stats.durationToday';
    }

    public function getData(array $options = []): mixed
    {
        $this->setQueryWithUser(false);
        $this->setBegin('00:00:00');
        $this->setEnd('23:59:59');

        return parent::getData($options);
    }
}
