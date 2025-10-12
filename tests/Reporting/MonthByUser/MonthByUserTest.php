<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\MonthByUser;

use App\Reporting\DateByUser;
use App\Reporting\MonthByUser\MonthByUser;
use App\Tests\Reporting\AbstractDateByUserTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MonthByUser::class)]
#[CoversClass(DateByUser::class)]
class MonthByUserTest extends AbstractDateByUserTestCase
{
    protected function createSut(): DateByUser
    {
        return new MonthByUser();
    }
}
