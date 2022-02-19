<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\AbstractUserList;
use App\Reporting\YearlyUserList;

/**
 * @covers \App\Reporting\YearlyUserList
 * @covers \App\Reporting\AbstractUserList
 */
class YearlyUserListTest extends AbstractUserListTest
{
    protected function createSut(): AbstractUserList
    {
        return new YearlyUserList();
    }
}
