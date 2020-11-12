<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;

final class Counter extends SimpleStatisticChart
{
    public function __construct(TimesheetRepository $repository)
    {
        parent::__construct($repository);
        $this->setOption('dataType', 'int');
    }
}
