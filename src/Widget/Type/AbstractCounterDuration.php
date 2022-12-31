<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;

abstract class AbstractCounterDuration extends AbstractSimpleStatisticChart
{
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'duration',
        ], parent::getOptions($options));
    }

    public function getData(array $options = []): mixed
    {
        $this->setQuery(TimesheetRepository::STATS_QUERY_DURATION);

        return parent::getData($options);
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-duration.html.twig';
    }
}
