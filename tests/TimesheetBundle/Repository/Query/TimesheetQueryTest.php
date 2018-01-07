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

use AppBundle\Repository\Query\BaseQuery;
use \PHPUnit\Framework\TestCase;
use TimesheetBundle\Repository\Query\TimesheetQuery;

/**
 * @covers \TimesheetBundle\Repository\Query\TimesheetQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetQueryTest extends TestCase
{

    public function testSetOrder()
    {
        $class = new \ReflectionClass(new BaseQuery());
        $this->assertTrue($class->hasProperty('order'));
        $this->assertTrue($class->hasProperty('orderBy'));

        $sut = new TimesheetQuery();

        $this->assertEquals(TimesheetQuery::ORDER_DESC, $sut->getOrder());
        $this->assertEquals('begin', $sut->getOrderBy());

        $sut->setOrder(TimesheetQuery::ORDER_ASC);
        $sut->setOrderBy('id');

        $this->assertEquals(TimesheetQuery::ORDER_ASC, $sut->getOrder());
        $this->assertEquals('id', $sut->getOrderBy());
    }
}
