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

final class ActiveUsersYear extends CounterYear
{
    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        parent::__construct($repository, $systemConfiguration);
        $this->setId('activeUsersYear');
        $this->setOption('icon', 'user');
        $this->setOption('color', 'yellow');
        $this->setTitle('stats.userActiveYear');
    }

    public function getData(array $options = [])
    {
        $this->titleYear = 'stats.userActiveFinancialYear';
        $this->setQuery(TimesheetRepository::STATS_QUERY_USER);
        $this->setQueryWithUser(false);

        return parent::getData($options);
    }
}
