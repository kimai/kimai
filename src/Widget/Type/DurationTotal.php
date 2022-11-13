<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

final class DurationTotal extends AbstractCounterDuration
{
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_TOTAL], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_other_timesheet'];
    }

    public function getId(): string
    {
        return 'durationTotal';
    }

    public function getTitle(): string
    {
        return 'stats.durationTotal';
    }

    public function getData(array $options = []): mixed
    {
        $this->setQueryWithUser(false);

        return parent::getData($options);
    }
}
