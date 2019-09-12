<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\TeamQuery;

/**
 * @covers \App\Repository\Query\TeamQuery
 */
class TeamQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new TeamQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(TeamQuery::class, $sut);

        $this->assertResetByFormError(new TeamQuery(), 'name');
    }
}
