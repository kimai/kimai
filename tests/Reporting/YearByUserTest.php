<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\DateByUser;
use App\Reporting\YearByUser;

/**
 * @covers \App\Reporting\YearByUser
 * @covers \App\Reporting\DateByUser
 */
class YearByUserTest extends AbstractDateByUserTest
{
    protected function createSut(): DateByUser
    {
        return new YearByUser();
    }
}
