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
use App\Repository\Query\BaseFormTypeQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityFormTypeQuery::class)]
#[CoversClass(BaseFormTypeQuery::class)]
class ActivityFormTypeQueryTest extends AbstractBaseFormTypeQueryTestCase
{
    public function testQuery(): void
    {
        $sut = new ActivityFormTypeQuery();

        self::assertTrue($sut->isGlobalsOnly());

        $this->assertBaseQuery($sut);

        self::assertFalse($sut->isGlobalsOnly());

        self::assertFalse($sut->isAllowActivityPreselect());
        $sut->setAllowActivityPreselect(true);
        self::assertTrue($sut->isAllowActivityPreselect());

        $activity = new Activity();
        self::assertNull($sut->getActivityToIgnore());
        self::assertInstanceOf(ActivityFormTypeQuery::class, $sut->setActivityToIgnore($activity));
        self::assertSame($activity, $sut->getActivityToIgnore());
    }
}
