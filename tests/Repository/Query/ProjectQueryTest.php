<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\ProjectQuery;
use App\Repository\Query\VisibilityInterface;

/**
 * @covers \App\Repository\Query\ProjectQuery
 * @covers \App\Repository\Query\BaseQuery
 */
class ProjectQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(VisibilityInterface::class, $sut);

        $this->assertCustomer($sut);

        $this->assertResetByFormError(new ProjectQuery(), 'name');

        self::assertNull($sut->getProjectStart());
        self::assertNull($sut->getProjectEnd());
    }

    public function testSetter()
    {
        $sut = new ProjectQuery();

        $start = new \DateTime();
        $sut->setProjectStart($start);
        self::assertSame($start, $sut->getProjectStart());

        $end = new \DateTime('-1 day');
        $sut->setProjectEnd($end);
        self::assertSame($end, $sut->getProjectEnd());
    }
}
