<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\YearByUser;

use App\Reporting\DateByUser;
use App\Reporting\YearByUser\YearByUser;
use App\Tests\Reporting\AbstractDateByUserTestCase;

/**
 * @covers \App\Reporting\YearByUser\YearByUser
 * @covers \App\Reporting\DateByUser
 */
class YearByUserTest extends AbstractDateByUserTestCase
{
    protected function createSut(): DateByUser
    {
        return new YearByUser();
    }
}
