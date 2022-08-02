<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\WeeklyUserList;

use App\Reporting\AbstractUserList;
use App\Reporting\WeeklyUserList\WeeklyUserList;
use App\Tests\Reporting\AbstractUserListTest;

/**
 * @covers \App\Reporting\WeeklyUserList\WeeklyUserList
 * @covers \App\Reporting\AbstractUserList
 */
class WeeklyUserListTest extends AbstractUserListTest
{
    protected function createSut(): AbstractUserList
    {
        return new WeeklyUserList();
    }
}
