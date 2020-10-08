<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\DateByUser;
use App\Reporting\WeekByUser;

/**
 * @covers \App\Reporting\WeekByUser
 * @covers \App\Reporting\DateByUser
 */
class WeekByUserTest extends AbstractDateByUserTest
{
    protected function createSut(): DateByUser
    {
        return new WeekByUser();
    }
}
