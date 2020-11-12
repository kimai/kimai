<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

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
        self::assertNull($sut->getTeamLead());
        self::assertInstanceOf(Collection::class, $sut->getUsers());
        self::assertEquals(0, $sut->getUsers()->count());
        self::assertInstanceOf(Collection::class, $sut->getCustomers());
        self::assertEquals(0, $sut->getCustomers()->count());
        self::assertInstanceOf(Collection::class, $sut->getProjects());
        self::assertEquals(0, $sut->getProjects()->count());
        self::assertInstanceOf(Collection::class, $sut->getActivities());
        self::assertEquals(0, $sut->getActivities()->count());
    }

    public function testSetterAndGetter()
    {
        $sut = new Team();
        self::assertInstanceOf(Team::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        $user = (new User())->setAlias('Foo!');
        self::assertInstanceOf(Team::class, $sut->setTeamLead($user));
        self::assertSame($user, $sut->getTeamLead());

        self::assertFalse($sut->isTeamlead(new User()));
        self::assertTrue($sut->isTeamlead($user));
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
        self::assertEquals(1, $sut->getUsers()->count());
        $actual = $sut->getUsers()[0];
        self::assertSame($actual, $user);
        self::assertSame($sut, $user->getTeams()[0]);
        self::assertFalse($sut->hasUser(new User()));
        self::assertTrue($sut->hasUser($user));
        $sut->removeUser(new User());
        self::assertEquals(1, $sut->getUsers()->count());
        $sut->removeUser($user);
        self::assertEquals(0, $sut->getUsers()->count());
    }
}
