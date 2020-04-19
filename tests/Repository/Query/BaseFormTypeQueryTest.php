<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Query\BaseFormTypeQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Query\BaseQuery
 */
abstract class BaseFormTypeQueryTest extends TestCase
{
    protected function assertBaseQuery(BaseFormTypeQuery $sut)
    {
        $this->assertActivity($sut);
        $this->assertProject($sut);
        $this->assertCustomer($sut);
        $this->assertTeams($sut);
        $this->assertUser($sut);
    }

    protected function assertUser(BaseFormTypeQuery $sut)
    {
        self::assertEmpty($sut->getUser());
        $user = new User();
        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }

    protected function assertTeams(BaseFormTypeQuery $sut)
    {
        self::assertEmpty($sut->getTeams());

        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam(new Team()));
        self::assertEquals(1, \count($sut->getTeams()));

        $team = new Team();
        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam($team));
        self::assertEquals(1, \count($sut->getTeams()));
        self::assertSame($team, $sut->getTeams()[0]);
    }

    protected function assertActivity(BaseFormTypeQuery $sut)
    {
        $this->assertNull($sut->getActivity());
        $this->assertEquals([], $sut->getActivities());
        $this->assertFalse($sut->hasActivities());

        $expected = new Activity();
        $expected->setName('foo-bar');

        $sut->setActivity($expected);
        $this->assertEquals($expected, $sut->getActivity());

        $sut->setActivities([]);
        $this->assertEquals([], $sut->getActivities());

        $sut->addActivity($expected);
        $this->assertEquals([$expected], $sut->getActivities());
        $this->assertTrue($sut->hasActivities());

        $expected2 = new Activity();
        $expected2->setName('foo-bar2');

        $sut->addActivity($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getActivities());

        $sut->setActivity(null);
        $this->assertNull($sut->getActivity());
        $this->assertFalse($sut->hasActivities());

        // make sure int is allowed as well
        $sut->setActivities([99]);
        $this->assertEquals(99, $sut->getActivity());
        $this->assertEquals([99], $sut->getActivities());
    }

    protected function assertCustomer(BaseFormTypeQuery $sut)
    {
        $this->assertNull($sut->getCustomer());
        $this->assertEquals([], $sut->getCustomers());
        $this->assertFalse($sut->hasCustomers());

        $expected = new Customer();
        $expected->setName('foo-bar');

        $sut->setCustomer($expected);
        $this->assertEquals($expected, $sut->getCustomer());

        $sut->setCustomers([]);
        $this->assertEquals([], $sut->getCustomers());

        $sut->addCustomer($expected);
        $this->assertEquals([$expected], $sut->getCustomers());
        $this->assertTrue($sut->hasCustomers());

        $expected2 = new Customer();
        $expected2->setName('foo-bar2');

        $sut->addCustomer($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getCustomers());

        $sut->setCustomer(null);
        $this->assertNull($sut->getCustomer());
        $this->assertFalse($sut->hasCustomers());

        // make sure int is allowed as well
        $sut->setCustomers([99]);
        $this->assertEquals(99, $sut->getCustomer());
        $this->assertEquals([99], $sut->getCustomers());
    }

    protected function assertProject(BaseFormTypeQuery $sut)
    {
        $this->assertNull($sut->getProject());
        $this->assertEquals([], $sut->getProjects());
        $this->assertFalse($sut->hasProjects());

        $expected = new Project();
        $expected->setName('foo-bar');

        $sut->setProject($expected);
        $this->assertEquals($expected, $sut->getProject());

        $sut->setProjects([]);
        $this->assertEquals([], $sut->getProjects());

        $sut->addProject($expected);
        $this->assertEquals([$expected], $sut->getProjects());
        $this->assertTrue($sut->hasProjects());

        $expected2 = new Project();
        $expected2->setName('foo-bar2');

        $sut->addProject($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getProjects());

        $sut->setProject(null);
        $this->assertNull($sut->getProject());
        $this->assertFalse($sut->hasProjects());

        // make sure int is allowed as well
        $sut->setProjects([99]);
        $this->assertEquals(99, $sut->getProject());
        $this->assertEquals([99], $sut->getProjects());
    }
}
