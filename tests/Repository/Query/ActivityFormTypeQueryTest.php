<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Repository\Query\ActivityFormTypeQuery;

/**
 * @covers \App\Repository\Query\ActivityFormTypeQuery
 * @covers \App\Repository\Query\BaseFormTypeQuery
 */
class ActivityFormTypeQueryTest extends BaseFormTypeQueryTest
{
    public function testQuery()
    {
        $sut = new ActivityFormTypeQuery();

        self::assertTrue($sut->isGlobalsOnly());

        $this->assertBaseQuery($sut);

        self::assertFalse($sut->isGlobalsOnly());

        $activity = new Activity();
        self::assertNull($sut->getActivityToIgnore());
        self::assertInstanceOf(ActivityFormTypeQuery::class, $sut->setActivityToIgnore($activity));
        self::assertSame($activity, $sut->getActivityToIgnore());
    }
}
