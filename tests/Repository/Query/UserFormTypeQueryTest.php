<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Repository\Query\UserFormTypeQuery;

/**
 * @covers \App\Repository\Query\UserFormTypeQuery
 */
class UserFormTypeQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new UserFormTypeQuery();

        self::assertEmpty($sut->getTeams());
        self::assertInstanceOf(UserFormTypeQuery::class, $sut->addTeam(new Team()));
        self::assertCount(1, $sut->getTeams());

        $user = new User();
        self::assertNull($sut->getUser());
        self::assertInstanceOf(UserFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }
}
