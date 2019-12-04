<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\VisibilityInterface;

/**
 * @covers \App\Repository\Query\ProjectQuery
 */
class ProjectQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(VisibilityInterface::class, $sut);

        $this->assertNull($sut->getCustomer());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);

        $this->assertEquals($expected, $sut->getCustomer());

        // make sure int is allowed as well
        $sut->setCustomer(99);
        $this->assertEquals(99, $sut->getCustomer());

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
