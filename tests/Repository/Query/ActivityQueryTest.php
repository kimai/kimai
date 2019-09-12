<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\VisibilityQuery;

/**
 * @covers \App\Repository\Query\ActivityQuery
 */
class ActivityQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ActivityQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(VisibilityQuery::class, $sut);

        $this->assertNull($sut->getCustomer());
        $this->assertNull($sut->getProject());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);

        $this->assertEquals($expected, $sut->getCustomer());

        $expected = new Project();
        $expected->setName('foo-bar');
        $sut->setProject($expected);

        $this->assertEquals($expected, $sut->getProject());

        // make sure int is allowed as well
        $sut->setProject(99);
        $this->assertEquals(99, $sut->getProject());

        $sut->setCustomer(99);
        $this->assertEquals(99, $sut->getCustomer());

        $this->assertResetByFormError(new ActivityQuery(), 'name');
    }
}
