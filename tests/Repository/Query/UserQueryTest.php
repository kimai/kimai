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
use App\Repository\Query\VisibilityInterface;

/**
 * @covers \App\Repository\Query\UserQuery
 */
class UserQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new UserQuery();
        $this->assertBaseQuery($sut, 'user');
        $this->assertInstanceOf(VisibilityInterface::class, $sut);
        $this->assertRole($sut);
        $this->assertSearchTeam($sut);

        $this->assertResetByFormError(new UserQuery(), 'user');
    }

    protected function assertRole(UserQuery $sut)
    {
        $this->assertNull($sut->getRole());
        $sut->setRole('ROLE_USER');
        $this->assertEquals('ROLE_USER', $sut->getRole());
    }

    protected function assertSearchTeam(UserQuery $sut)
    {
        $team = new Team('foo');
        $this->assertIsArray($sut->getSearchTeams());
        $this->assertEmpty($sut->getSearchTeams());
        $sut->setSearchTeams([$team, new Team('foo')]);
        $this->assertCount(2, $sut->getSearchTeams());
        $this->assertSame($team, $sut->getSearchTeams()[0]);
    }

    public function testSystemAccount()
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
