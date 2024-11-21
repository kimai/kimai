<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\ActivityQuery;

/**
 * @covers \App\Repository\Query\ActivityQuery
 * @covers \App\Repository\Query\BaseQuery
 */
class ActivityQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new ActivityQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertResetByFormError(new ActivityQuery(), 'name');
    }
}
