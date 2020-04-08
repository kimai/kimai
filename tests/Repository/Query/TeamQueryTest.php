<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\User;
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

        $this->assertUsers($sut);

        $this->assertResetByFormError(new TeamQuery(), 'name');
    }

    protected function assertUsers(TeamQuery $sut)
    {
        $this->assertEmpty($sut->getUsers());

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $sut->addUser($user);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $sut->addUser($user);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(13);
        $sut->addUser($user);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(27);
        $sut->addUser($user);
        $sut->removeUser($user);

        $this->assertCount(2, $sut->getUsers());
    }
}
