<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\AbstractUserList;
use App\Reporting\MonthlyUserList;

/**
 * @covers \App\Reporting\MonthlyUserList
 * @covers \App\Reporting\AbstractUserList
 */
class MonthlyUserListTest extends AbstractUserListTest
{
    protected function createSut(): AbstractUserList
    {
        return new MonthlyUserList();
    }
}
