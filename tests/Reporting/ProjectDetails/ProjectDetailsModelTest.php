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
use App\Model\Statistic\Year;
use App\Reporting\ProjectDetails\ProjectDetailsModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectDetails\ProjectDetailsModel
 */
class ProjectDetailsModelTest extends TestCase
{
    public function testDefaults(): void
    {
        $project = new Project();
        $sut = new ProjectDetailsModel($project);

        self::assertSame($project, $sut->getProject());
        self::assertIsArray($sut->getActivities());
        self::assertIsArray($sut->getUserStats());
        self::assertIsArray($sut->getYears());
        self::assertIsArray($sut->getUserYears('2999'));
        self::assertIsArray($sut->getYearActivities('2999'));
        self::assertNull($sut->getYear('2999'));
        self::assertNull($sut->getUserYear('2999', new User()));
    }

    public function testGetYearsSorted(): void
    {
        $project = new Project();
        $sut = new ProjectDetailsModel($project);

        $y2022 = new Year('2022');
        $y1999 = new Year('1999');
        $y2001 = new Year('2001');
        $y1997 = new Year('1997');
        $y2015 = new Year('2015');

        $sut->setYears([
            $y2022,
            $y1999,
            $y2001,
            $y1997,
            $y2015,
        ]);

        $expected = [
            $y1997,
            $y1999,
            $y2001,
            $y2015,
            $y2022,
        ];

        self::assertEquals($expected, $sut->getYears());
    }
}
