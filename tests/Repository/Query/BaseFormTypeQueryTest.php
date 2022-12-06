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

        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam(new Team('foo')));
        self::assertCount(1, $sut->getTeams());

        $team = new Team('foo');
        self::assertInstanceOf(BaseFormTypeQuery::class, $sut->addTeam($team));
        self::assertCount(1, $sut->getTeams());
        /* @phpstan-ignore-next-line  */
        self::assertSame($team, $sut->getTeams()[0]);

        $sut->setTeams([]);
        self::assertEmpty($sut->getTeams());
        $sut->setTeams([new Team('foo'), new Team('foo')]);
        self::assertCount(2, $sut->getTeams());
    }

    protected function assertActivity(BaseFormTypeQuery $sut)
    {
        $expected = new Activity();
        $expected->setName('foo-bar');

        $sut->addActivity($expected);
        $this->assertEquals([$expected], $sut->getActivities());
        $this->assertTrue($sut->hasActivities());

        $expected2 = new Activity();
        $expected2->setName('foo-bar2');

        $sut->addActivity($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getActivities());

        $sut->setActivities([]);
        $this->assertEquals([], $sut->getActivities());
        $this->assertEquals([], $sut->getActivities());
        $this->assertFalse($sut->hasActivities());
        $this->assertFalse($sut->hasActivities());
    }

    protected function assertCustomer(BaseFormTypeQuery $sut)
    {
        $expected = new Customer('foo-bar');

        $sut->addCustomer($expected);
        $this->assertEquals([$expected], $sut->getCustomers());
        $this->assertTrue($sut->hasCustomers());

        $expected2 = new Customer('foo-bar2');

        $sut->addCustomer($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getCustomers());

        $sut->setCustomers([]);
        $this->assertEquals([], $sut->getCustomers());
        $this->assertEquals([], $sut->getCustomers());
        $this->assertFalse($sut->hasCustomers());
        $this->assertFalse($sut->hasCustomers());
    }

    protected function assertProject(BaseFormTypeQuery $sut)
    {
        $expected = new Project();
        $expected->setName('foo-bar');

        $sut->addProject($expected);
        $this->assertEquals([$expected], $sut->getProjects());
        $this->assertTrue($sut->hasProjects());

        $expected2 = new Project();
        $expected2->setName('foo-bar2');

        $sut->addProject($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getProjects());

        $sut->setProjects([]);
        $this->assertEquals([], $sut->getProjects());
        $this->assertEquals([], $sut->getProjects());
        $this->assertFalse($sut->hasProjects());
        $this->assertFalse($sut->hasProjects());

        // make sure int is allowed as well
        $sut->setProjects([99]);
        $this->assertEquals([99], $sut->getProjects());
    }
}
