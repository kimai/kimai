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
abstract class AbstractBaseFormTypeQueryTestCase extends TestCase
{
    public function assertBaseQuery(BaseFormTypeQuery $sut): void
    {
        $this->assertActivity($sut);
        $this->assertProject($sut);
        $this->assertCustomer($sut);
        $this->assertTeams($sut);
        $this->assertUser($sut);
    }

    public function assertUser(BaseFormTypeQuery $sut): void
    {
        self::assertEmpty($sut->getUser());
        $user = new User();
        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }

    public function assertTeams(BaseFormTypeQuery $sut): void
    {
        self::assertEmpty($sut->getTeams());

        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam(new Team('foo')));
        self::assertCount(1, $sut->getTeams());

        $team = new Team('foo');
        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam($team));
        self::assertCount(1, $sut->getTeams());
        self::assertSame($team, $sut->getTeams()[0]);

        $sut->setTeams([]);
        self::assertEmpty($sut->getTeams());
        $sut->setTeams([new Team('foo'), new Team('foo')]);
        self::assertCount(2, $sut->getTeams());
    }

    public function assertActivity(BaseFormTypeQuery $sut): void
    {
        $expected = new Activity();
        $expected->setName('foo-bar');

        $sut->addActivity($expected);
        self::assertEquals([$expected], $sut->getActivities());
        self::assertTrue($sut->hasActivities());

        $expected2 = new Activity();
        $expected2->setName('foo-bar2');

        $sut->addActivity($expected2);
        self::assertEquals([$expected, $expected2], $sut->getActivities());

        $sut->setActivities([]);
        self::assertEquals([], $sut->getActivities());
        self::assertEquals([], $sut->getActivities());
        self::assertFalse($sut->hasActivities());
        self::assertFalse($sut->hasActivities());
    }

    public function assertCustomer(BaseFormTypeQuery $sut): void
    {
        $expected = new Customer('foo-bar');

        $sut->addCustomer($expected);
        self::assertEquals([$expected], $sut->getCustomers());
        self::assertTrue($sut->hasCustomers());

        $expected2 = new Customer('foo-bar2');

        $sut->addCustomer($expected2);
        self::assertEquals([$expected, $expected2], $sut->getCustomers());

        $sut->setCustomers([]);
        self::assertEquals([], $sut->getCustomers());
        self::assertEquals([], $sut->getCustomers());
        self::assertFalse($sut->hasCustomers());
        self::assertFalse($sut->hasCustomers());
    }

    public function assertProject(BaseFormTypeQuery $sut): void
    {
        $expected = new Project();
        $expected->setName('foo-bar');

        $sut->addProject($expected);
        self::assertEquals([$expected], $sut->getProjects());
        self::assertTrue($sut->hasProjects());

        $expected2 = new Project();
        $expected2->setName('foo-bar2');

        $sut->addProject($expected2);
        self::assertEquals([$expected, $expected2], $sut->getProjects());

        $sut->setProjects([]);
        self::assertEquals([], $sut->getProjects());
        self::assertEquals([], $sut->getProjects());
        self::assertFalse($sut->hasProjects());
        self::assertFalse($sut->hasProjects());

        // make sure int is allowed as well
        $sut->setProjects([99]);
        self::assertEquals([99], $sut->getProjects());
    }
}
