<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\User;
use App\Repository\Query\UserFormTypeQuery;

/**
 * @covers \App\Repository\Query\UserFormTypeQuery
 * @covers \App\Repository\Query\BaseFormTypeQuery
 */
class UserFormTypeQueryTest extends BaseFormTypeQueryTest
{
    public function testQuery(): void
    {
        $sut = new UserFormTypeQuery();

        $this->assertBaseQuery($sut);
    }

    public function testUsersAreAlwaysIncluded()
    {
        $sut = new UserFormTypeQuery();

        $user = new User();
        $user->setUserIdentifier('foo');

        $users = [$user, new User(), new User()];

        self::assertEquals([], $sut->getUsersAlwaysIncluded());
        $sut->setUsersAlwaysIncluded($users);
        self::assertSame($users, $sut->getUsersAlwaysIncluded());

        self::assertEquals([], $sut->getUsersToIgnore());
        self::assertInstanceOf(UserFormTypeQuery::class, $sut->addUserToIgnore($users[0]));
        self::assertSame([$users[0]], $sut->getUsersToIgnore());
    }
}
