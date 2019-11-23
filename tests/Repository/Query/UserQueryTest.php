<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\UserQuery;
use App\Repository\Query\VisibilityQuery;

/**
 * @covers \App\Repository\Query\UserQuery
 */
class UserQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new UserQuery();
        $this->assertBaseQuery($sut, 'username');
        $this->assertInstanceOf(VisibilityQuery::class, $sut);
        $this->assertRole($sut);

        $this->assertResetByFormError(new UserQuery(), 'username');
    }

    protected function assertRole(UserQuery $sut)
    {
        $this->assertNull($sut->getRole());
        $sut->setRole('ROLE_USER');
        $this->assertEquals('ROLE_USER', $sut->getRole());
    }
}
