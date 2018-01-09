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
use TimesheetBundle\Repository\Query\CustomerQuery;

/**
 * @covers \TimesheetBundle\Repository\Query\CustomerQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new CustomerQuery();

        $this->assertBaseQuery($sut);
        $this->assertInstanceOf(VisibilityInterface::class, $sut);
        $this->assertArrayHasKey(VisibilityTrait::class, class_uses($sut));
    }
}
