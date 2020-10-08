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
class ProjectFormTypeQueryTest extends BaseFormTypeQueryTest
{
    public function testQuery()
    {
        $sut = new ProjectFormTypeQuery();

        $this->assertBaseQuery($sut);

        $project = new Project();
        self::assertNull($sut->getProjectToIgnore());
        self::assertInstanceOf(ProjectFormTypeQuery::class, $sut->setProjectToIgnore($project));
        self::assertSame($project, $sut->getProjectToIgnore());
    }
}
