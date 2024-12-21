<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Team;
use App\Repository\Query\UserQuery;

/**
 * @covers \App\Repository\Query\UserQuery
 */
class UserQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new UserQuery();
        $this->assertBaseQuery($sut, 'username');
        $this->assertRole($sut);
        $this->assertSearchTeam($sut);
        $this->assertResetByFormError(new UserQuery(), 'username');
    }

    protected function assertRole(UserQuery $sut): void
    {
        self::assertNull($sut->getRole());
        $sut->setRole('ROLE_USER');
        self::assertEquals('ROLE_USER', $sut->getRole());
    }

    protected function assertSearchTeam(UserQuery $sut): void
    {
        $team = new Team('foo');
        self::assertIsArray($sut->getSearchTeams());
        self::assertEmpty($sut->getSearchTeams());
        $sut->setSearchTeams([$team, new Team('foo')]);
        self::assertCount(2, $sut->getSearchTeams());
        self::assertSame($team, $sut->getSearchTeams()[0]);
    }

    public function testSystemAccount(): void
    {
        $sut = new UserQuery();
        self::assertNull($sut->getSystemAccount());
        $sut->setSystemAccount(false);
        self::assertFalse($sut->getSystemAccount());
        $sut->setSystemAccount(true);
        self::assertTrue($sut->getSystemAccount());
        $sut->setSystemAccount(null);
        self::assertNull($sut->getSystemAccount());
    }
}
