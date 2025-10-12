<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\MonthlyUserList;

use App\Reporting\AbstractUserList;
use App\Reporting\MonthlyUserList\MonthlyUserList;
use App\Tests\Reporting\AbstractUserListTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MonthlyUserList::class)]
#[CoversClass(AbstractUserList::class)]
class MonthlyUserListTest extends AbstractUserListTestCase
{
    protected function createSut(): AbstractUserList
    {
        return new MonthlyUserList();
    }
}
