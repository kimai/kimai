<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Model\MultiUserTimesheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultiUserTimesheet::class)]
class MultiUserTimesheetTest extends TestCase
{
    public function testDefaultCollectionsAreEmpty(): void
    {
        $sut = new MultiUserTimesheet();

        self::assertInstanceOf(Timesheet::class, $sut);
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($sut->getUsers());
    }

    public function testAddAndRemoveUser(): void
    {
        $sut = new MultiUserTimesheet();
        $user = $this->createUser('alpha');

        $sut->addUser($user);

        self::assertCount(1, $sut->getUsers());
        self::assertSame($user, $sut->getUsers()->first());

        $sut->removeUser($user);

        self::assertCount(0, $sut->getUsers());
    }

    public function testRemovingUnknownUserDoesNothing(): void
    {
        $sut = new MultiUserTimesheet();
        $user = $this->createUser('alpha');

        $sut->removeUser($user);

        self::assertCount(0, $sut->getUsers());
    }

    public function testRemovingUserOnlyRemovesOneDuplicateEntry(): void
    {
        $sut = new MultiUserTimesheet();
        $user = $this->createUser('alpha');

        $sut->addUser($user);
        $sut->addUser($user);

        self::assertCount(2, $sut->getUsers());

        $sut->removeUser($user);

        self::assertCount(1, $sut->getUsers());
        self::assertSame($user, $sut->getUsers()->first());
    }

    public function testAddAndRemoveTeam(): void
    {
        $sut = new MultiUserTimesheet();
        $team = new Team('Team Alpha');

        $sut->addTeam($team);

        self::assertCount(1, $sut->getTeams());
        self::assertSame($team, $sut->getTeams()->first());

        $sut->removeTeam($team);

        self::assertCount(0, $sut->getTeams());
    }

    public function testRemovingUnknownTeamDoesNothing(): void
    {
        $sut = new MultiUserTimesheet();
        $team = new Team('Team Alpha');

        $sut->removeTeam($team);

        self::assertCount(0, $sut->getTeams());
    }

    public function testRemovingTeamOnlyRemovesOneDuplicateEntry(): void
    {
        $sut = new MultiUserTimesheet();
        $team = new Team('Team Alpha');

        $sut->addTeam($team);
        $sut->addTeam($team);

        self::assertCount(2, $sut->getTeams());

        $sut->removeTeam($team);

        self::assertCount(1, $sut->getTeams());
        self::assertSame($team, $sut->getTeams()->first());
    }

    private function createUser(string $username): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setAlias($username);
        $user->setEmail($username . '@example.com');

        return $user;
    }
}
