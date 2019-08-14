<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Query\ProjectFormTypeQuery;

/**
 * @covers \App\Repository\Query\ProjectFormTypeQuery
 */
class ProjectFormTypeQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectFormTypeQuery();

        self::assertEmpty($sut->getTeams());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->addTeam(new Team()));
        self::assertCount(1, $sut->getTeams());

        $project = new Project();
        self::assertNull($sut->getProject());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->setProject($project));
        self::assertSame($project, $sut->getProject());

        $project = new Project();
        self::assertNull($sut->getProjectToIgnore());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->setProjectToIgnore($project));
        self::assertSame($project, $sut->getProjectToIgnore());

        $customer = new Customer();
        self::assertNull($sut->getCustomer());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        $user = new User();
        self::assertNull($sut->getUser());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }
}
