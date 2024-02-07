<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectDetails;

use App\Entity\Project;
use App\Entity\User;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectDetails\ProjectDetailsQuery
 */
class ProjectDetailsQueryTest extends TestCase
{
    public function testDefaults(): void
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectDetailsQuery($date, $user);

        self::assertSame($date, $sut->getToday());
        self::assertSame($user, $sut->getUser());
        self::assertNull($sut->getProject());
    }

    public function testSetterGetter(): void
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectDetailsQuery($date, $user);

        $project = new Project();

        $sut->setProject($project);

        self::assertSame($project, $sut->getProject());
    }
}
