<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;

abstract class AbstractActiveUsers extends AbstractSimpleStatisticChart
{
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'users',
        ], parent::getOptions($options));
    }

    public function getData(array $options = []): mixed
    {
        $this->setQueryWithUser(false);
        $this->setQuery(TimesheetRepository::STATS_QUERY_USER);

        return parent::getData($options);
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getPermissions(): array
    {
        return ['ROLE_TEAMLEAD'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
