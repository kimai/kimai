<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Repository\TimesheetRepository;

final class UserDurationYear extends CounterYear
{
    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        parent::__construct($repository, $systemConfiguration);
        $this->setId('userDurationYear');
        $this->setOption('dataType', 'duration');
        $this->setOption('icon', 'duration');
        $this->setOption('color', 'yellow');
    }

    public function getData(array $options = [])
    {
        $this->setTitle('stats.durationYear');
        $this->titleYear = 'stats.durationFinancialYear';
        $this->setQuery(TimesheetRepository::STATS_QUERY_DURATION);
        $this->setQueryWithUser(true);

        return parent::getData($options);
    }
}
