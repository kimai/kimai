<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Project;
use App\Repository\Query\ProjectFormTypeQuery;

/**
 * @covers \App\Repository\Query\ProjectFormTypeQuery
 * @covers \App\Repository\Query\BaseFormTypeQuery
 */
class ProjectFormTypeQueryTest extends AbstractBaseFormTypeQueryTestCase
{
    public function testQuery(): void
    {
        $sut = new ProjectFormTypeQuery();

        $this->assertBaseQuery($sut);

        $project = new Project();
        self::assertFalse($sut->withCustomer());
        $sut->setWithCustomer(false);
        self::assertFalse($sut->withCustomer());
        $sut->setWithCustomer(true);
        self::assertTrue($sut->withCustomer());
        self::assertNull($sut->getProjectToIgnore());
        $sut->setProjectToIgnore($project);
        self::assertSame($project, $sut->getProjectToIgnore());

        self::assertNotNull($sut->getProjectStart());
        self::assertNotNull($sut->getProjectEnd());

        $date = new \DateTime('2019-04-20');
        $sut->setProjectStart($date);
        self::assertSame($date, $sut->getProjectStart());

        $date = new \DateTime('2020-01-01');
        $sut->setProjectEnd($date);
        self::assertSame($date, $sut->getProjectEnd());
    }
}
