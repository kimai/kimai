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

use App\Repository\Query\VisibilityQuery;
use App\Repository\Query\CustomerQuery;

/**
 * @covers \App\Repository\Query\CustomerQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new CustomerQuery();

        $this->assertBaseQuery($sut);
        $this->assertInstanceOf(VisibilityQuery::class, $sut);
    }
}
