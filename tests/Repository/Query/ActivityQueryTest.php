<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
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
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ActivityQuery();

        $this->assertBaseQuery($sut);
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
    }
}
