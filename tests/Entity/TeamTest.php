<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Constants;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Team
 */
class TeamTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Team('foo');
        self::assertNull($sut->getId());
        self::assertFalse($sut->hasUsers());
        self::assertFalse($sut->hasTeamleads());
        self::assertIsArray($sut->getTeamleads());
        self::assertEmpty($sut->getTeamleads());
        self::assertInstanceOf(Collection::class, $sut->getCustomers());
        self::assertEquals(0, $sut->getCustomers()->count());
        self::assertInstanceOf(Collection::class, $sut->getProjects());
        self::assertEquals(0, $sut->getProjects()->count());
        self::assertInstanceOf(Collection::class, $sut->getActivities());
        self::assertEquals(0, $sut->getActivities()->count());
    }

    public function testColor()
    {
        $sut = new Team('foo');
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor('#000000');
        self::assertEquals('#000000', $sut->getColor());
        self::assertTrue($sut->hasColor());
    }

    public function testTeamMemberships()
    {
        $user = new User();
        $user2 = new User();

        $member = new TeamMember();
        $member->setUser($user);

        $member2 = new TeamMember();
        $member2->setUser($user);
        $member2->setTeam(new Team('foo'));

        $sut = new Team('foo');
        self::assertFalse($sut->isTeamlead($user));
        self::assertCount(0, $sut->getTeamleads());
        self::assertCount(0, $sut->getMembers());
        self::assertFalse($sut->hasMember($member));
        $sut->removeMember($member);
        $sut->removeMember($member2);
        self::assertFalse($sut->hasUser($user));
        $sut->addMember($member);

        self::assertTrue($sut->hasUser($user));
        self::assertFalse($sut->isTeamlead($user));
        $sut->addTeamlead($user);
        self::assertTrue($sut->isTeamlead($user));
        $sut->addUser($user);
        self::assertTrue($sut->isTeamlead($user));

        self::assertCount(1, $sut->getMembers());
        $sut->removeMember($member2);
        self::assertCount(1, $sut->getMembers());
        $sut->removeMember($member);
        self::assertCount(0, $sut->getMembers());
        self::assertFalse($sut->isTeamlead($user));

        $member = new TeamMember();
        $member->setUser($user);

        $sut->addMember($member);
        $member->setTeamlead(true);
        self::assertTrue($sut->isTeamlead($user));
        self::assertCount(1, $sut->getMembers());
        $sut->addTeamlead($user2);
        self::assertCount(2, $sut->getMembers());
        self::assertTrue($sut->isTeamlead($user2));
        $sut->demoteTeamlead($user2);
        self::assertCount(2, $sut->getMembers());
        self::assertFalse($sut->isTeamlead($user2));

        $member21 = new TeamMember();
        $member21->setUser($user);

        self::assertNull($member21->getTeam());
        // this will not actually add it
        $sut->addMember($member21);
        self::assertSame($sut, $member21->getTeam());
        self::assertCount(2, $sut->getMembers());
    }

    public function testTeamMembershipsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $sut = new Team('foo');
        $member = new TeamMember();
        $member->setTeam(new Team('foo'));
        $sut->addMember($member);
    }

    public function testSetterAndGetter()
    {
        $sut = new Team('foo-bar');
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(99);
        $user->method('getAlias')->willReturn('Foo!');
        $sut->addTeamlead($user);
        self::assertSame($user, $sut->getTeamleads()[0]);
        self::assertTrue($sut->hasTeamleads());

        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        self::assertFalse($sut->isTeamlead(new User()));
        self::assertTrue($sut->isTeamlead($user));
        self::assertCount(1, $sut->getTeamleads());
        $sut->addTeamlead($user1);
        self::assertCount(2, $sut->getTeamleads());
        self::assertCount(2, $sut->getMembers());
        $sut->addUser($user2);
        self::assertCount(3, $sut->getMembers());
        self::assertCount(2, $sut->getTeamleads());
        $sut->addUser($user2);
        self::assertCount(3, $sut->getMembers());
        self::assertCount(2, $sut->getTeamleads());
    }

    public function testCustomer()
    {
        $customer = new Customer('foo');
        self::assertEmpty($customer->getTeams());

        $sut = new Team('foo');
        self::assertFalse($sut->hasCustomer($customer));
        $sut->addCustomer($customer);
        self::assertEquals(1, $sut->getCustomers()->count());
        self::assertTrue($sut->hasCustomer($customer));
        $actual = $sut->getCustomers()[0];
        self::assertSame($actual, $customer);
        self::assertSame($sut, $customer->getTeams()[0]);
        $sut->removeCustomer(new Customer('foo'));
        self::assertEquals(1, $sut->getCustomers()->count());
        $sut->removeCustomer($customer);
        self::assertEquals(0, $sut->getCustomers()->count());
    }

    public function testProject()
    {
        $project = new Project();
        $project->setName('foo');
        self::assertEmpty($project->getTeams());

        $sut = new Team('foo');
        self::assertFalse($sut->hasProject($project));
        $sut->addProject($project);
        self::assertEquals(1, $sut->getProjects()->count());
        self::assertTrue($sut->hasProject($project));
        $actual = $sut->getProjects()[0];
        self::assertSame($actual, $project);
        self::assertSame($sut, $project->getTeams()[0]);
        $sut->removeProject(new Project());
        self::assertEquals(1, $sut->getProjects()->count());
        $sut->removeProject($project);
        self::assertEquals(0, $sut->getProjects()->count());
    }

    public function testActivities()
    {
        $activity = new Activity();
        $activity->setName('foo');
        self::assertEmpty($activity->getTeams());

        $sut = new Team('foo');
        self::assertFalse($sut->hasActivity($activity));
        $sut->addActivity($activity);
        self::assertEquals(1, $sut->getActivities()->count());
        self::assertTrue($sut->hasActivity($activity));
        $actual = $sut->getActivities()[0];
        self::assertSame($actual, $activity);
        self::assertSame($sut, $activity->getTeams()[0]);
        $sut->removeActivity(new Activity());
        self::assertEquals(1, $sut->getActivities()->count());
        $sut->removeActivity($activity);
        self::assertEquals(0, $sut->getActivities()->count());
    }

    public function testUsers()
    {
        $user = new User();
        $user->setAlias('foo');
        self::assertEmpty($user->getTeams());

        $sut = new Team('foo');
        $sut->addUser($user);
        self::assertCount(1, $sut->getUsers());

        $users = $sut->getUsers();
        $actual = $users[0];
        self::assertSame($actual, $user);

        $teams = $user->getTeams();
        self::assertSame($sut, $teams[0]);
        self::assertFalse($sut->hasUser(new User()));
        self::assertTrue($sut->hasUser($user));
        $sut->removeUser(new User());
        self::assertCount(1, $sut->getUsers());
        $sut->removeUser($user);
        self::assertCount(0, $sut->getUsers());
        $sut->addTeamlead(new User());
        self::assertCount(1, $sut->getUsers());
    }

    public function testClone()
    {
        $c = new Customer('Foo');
        $p = new Project();
        $p->setName('Bar');
        $a = new Activity();
        $a->setName('Hello');
        $u = new User();
        $u->setAlias('World');
        $member = new TeamMember();
        $member->setUser(new User());

        $team = new Team('foo');
        $team->addCustomer($c);
        $team->addCustomer(new Customer('foo'));
        $team->addProject($p);
        $team->addProject(new Project());
        $team->addActivity($a);
        $team->addActivity(new Activity());

        $team->addTeamlead($u);
        $team->addMember($member);
        $team->addUser(new User());

        $reflection = new \ReflectionClass($team);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($team, 99);
        $property->setAccessible(false);
        self::assertEquals(99, $team->getId());

        $sut = clone $team;
        self::assertNull($sut->getId());
        self::assertCount(2, $sut->getCustomers());
        self::assertCount(2, $sut->getProjects());
        self::assertCount(2, $sut->getActivities());
        self::assertCount(1, $sut->getTeamleads());
        self::assertCount(3, $sut->getMembers());
        self::assertCount(3, $sut->getUsers());
        self::assertSame($c, $sut->getCustomers()[0]);
        self::assertSame($p, $sut->getProjects()[0]);
        self::assertSame($a, $sut->getActivities()[0]);
        self::assertSame($u, $sut->getTeamleads()[0]);
        self::assertSame($u, $sut->getMembers()[0]->getUser());
        self::assertSame($member->getUser(), $sut->getUsers()[1]);
    }
}
