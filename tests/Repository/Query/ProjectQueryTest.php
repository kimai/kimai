<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\ProjectQuery;

/**
 * @covers \App\Repository\Query\ProjectQuery
 * @covers \App\Repository\Query\BaseQuery
 */
class ProjectQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new ProjectQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertCustomer($sut);
        $this->assertResetByFormError(new ProjectQuery(), 'name');

        self::assertNull($sut->getProjectStart());
        self::assertNull($sut->getProjectEnd());
        self::assertNull($sut->getGlobalActivities());
    }

    public function testSetter(): void
    {
        $sut = new ProjectQuery();

        $start = new \DateTime();
        $sut->setProjectStart($start);
        self::assertSame($start, $sut->getProjectStart());

        $end = new \DateTime('-1 day');
        $sut->setProjectEnd($end);
        self::assertSame($end, $sut->getProjectEnd());

        $sut->setGlobalActivities(false);
        self::assertFalse($sut->getGlobalActivities());

        $sut->setGlobalActivities(true);
        self::assertTrue($sut->getGlobalActivities());
    }
}
