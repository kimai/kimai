<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\CustomerQuery;

/**
 * @covers \App\Repository\Query\CustomerQuery
 */
class CustomerQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new CustomerQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertResetByFormError(new CustomerQuery(), 'name');
    }
}
