<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Entity\Project;
use App\Repository\Query\ActivityFormTypeQuery;

/**
 * @covers \App\Repository\Query\ActivityFormTypeQuery
 */
class ActivityFormTypeQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ActivityFormTypeQuery();

        self::assertTrue($sut->isGlobalsOnly());

        $project = new Project();
        self::assertNull($sut->getProject());
        self::assertInstanceOf(ActivityFormTypeQuery::class, $sut->setProject($project));
        self::assertSame($project, $sut->getProject());

        $activity = new Activity();
        self::assertNull($sut->getActivity());
        self::assertInstanceOf(ActivityFormTypeQuery::class, $sut->setActivity($activity));
        self::assertSame($activity, $sut->getActivity());

        self::assertFalse($sut->isGlobalsOnly());

        $activity = new Activity();
        self::assertNull($sut->getActivityToIgnore());
        self::assertInstanceOf(ActivityFormTypeQuery::class, $sut->setActivityToIgnore($activity));
        self::assertSame($activity, $sut->getActivityToIgnore());
    }
}
