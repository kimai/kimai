<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\YearlyUserList;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Reporting\AbstractUserList;
use App\Reporting\YearlyUserList\YearlyUserList;
use App\Tests\Reporting\AbstractUserListTestCase;

#[CoversClass(YearlyUserList::class)]
#[CoversClass(AbstractUserList::class)]
class YearlyUserListTest extends AbstractUserListTestCase
{
    protected function createSut(): AbstractUserList
    {
        return new YearlyUserList();
    }
}
