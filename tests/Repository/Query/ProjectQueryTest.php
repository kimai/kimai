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
use App\Repository\Query\VisibilityQuery;

/**
 * @covers \App\Repository\Query\ProjectQuery
 */
class ProjectQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(VisibilityQuery::class, $sut);

        $this->assertNull($sut->getCustomer());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);

        $this->assertEquals($expected, $sut->getCustomer());

        // make sure int is allowed as well
        $sut->setCustomer(99);
        $this->assertEquals(99, $sut->getCustomer());
    }
}
