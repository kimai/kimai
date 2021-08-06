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
        $sut = new Team();
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
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
        $sut = new Team();
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setColor('#000000');
        self::assertEquals('#000000', $sut->getColor());
        self::assertTrue($sut->hasColor());
    }

    public function testSetterAndGetter()
    {
        $sut = new Team();
        self::assertInstanceOf(Team::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(99);
        $user->method('getAlias')->willReturn('Foo!');
        $sut->addTeamLead($user);
        self::assertSame($user, $sut->getTeamLeads()[0]);
        self::assertTrue($sut->hasTeamleads());

        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        self::assertFalse($sut->isTeamlead(new User()));
        self::assertTrue($sut->isTeamlead($user));
        self::assertCount(1, $sut->getTeamLeads());
        $sut->addTeamLead($user1);
        self::assertCount(2, $sut->getTeamLeads());
        self::assertCount(2, $sut->getMembers());
        $sut->addUser($user2);
        self::assertCount(3, $sut->getMembers());
        self::assertCount(2, $sut->getTeamLeads());
        $sut->addUser($user2);
        self::assertCount(3, $sut->getMembers());
        self::assertCount(2, $sut->getTeamLeads());
    }

    /**
     * @group legacy
     */
    public function testSetterAndGetterDeprecated()
    {
        $sut = new Team();

        self::assertNull($sut->getTeamLead());
        self::assertIsArray($sut->getUsers());
        self::assertEmpty($sut->getUsers());

        $user = (new User())->setAlias('Foo!');
        self::assertCount(0, $sut->getUsers());
        $sut->setTeamLead($user);
        self::assertSame($user, $sut->getTeamLead());
        self::assertTrue($sut->hasTeamleads());
        self::assertCount(1, $sut->getUsers());
        self::assertCount(1, $sut->getTeamLeads());
        self::assertCount(1, $sut->getMembers());
    }

    public function testCustomer()
    {
        $customer = new Customer();
        $customer->setName('foo');
        self::assertEmpty($customer->getTeams());

        $sut = new Team();
        self::assertFalse($sut->hasCustomer($customer));
        $sut->addCustomer($customer);
        self::assertEquals(1, $sut->getCustomers()->count());
        self::assertTrue($sut->hasCustomer($customer));
        $actual = $sut->getCustomers()[0];
        self::assertSame($actual, $customer);
        self::assertSame($sut, $customer->getTeams()[0]);
        $sut->removeCustomer(new Customer());
        self::assertEquals(1, $sut->getCustomers()->count());
        $sut->removeCustomer($customer);
        self::assertEquals(0, $sut->getCustomers()->count());
    }

    public function testProject()
    {
        $project = new Project();
        $project->setName('foo');
        self::assertEmpty($project->getTeams());

        $sut = new Team();
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

        $sut = new Team();
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

        $sut = new Team();
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
        $sut->addTeamLead(new User());
        self::assertCount(1, $sut->getUsers());
    }
}
