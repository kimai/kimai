<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiTest\TimesheetBundle\Repository\Query;

use AppBundle\Repository\Query\VisibilityInterface;
use AppBundle\Repository\Query\VisibilityTrait;
use KimaiTest\AppBundle\Repository\Query\BaseQueryTest;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Repository\Query\ProjectQuery;

/**
 * @covers \TimesheetBundle\Repository\Query\ProjectQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectQuery();

        $this->assertBaseQuery($sut);
        $this->assertInstanceOf(VisibilityInterface::class, $sut);
        $this->assertArrayHasKey(VisibilityTrait::class, class_uses($sut));

        $this->assertNull($sut->getCustomer());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);

        $this->assertEquals($expected, $sut->getCustomer());
    }
}
