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
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Repository\Query\ActivityQuery;

/**
 * @covers \TimesheetBundle\Repository\Query\ActivityQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ActivityQuery();

        $this->assertBaseQuery($sut);
        $this->assertInstanceOf(VisibilityInterface::class, $sut);
        $this->assertArrayHasKey(VisibilityTrait::class, class_uses($sut));

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
