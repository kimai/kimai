<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Constants;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Utils\Color;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Color
 */
class ColorTest extends TestCase
{
    public function testGetColorAndGetTimesheetColor()
    {
        $sut = new Color();

        $globalActivity = new Activity();
        self::assertNull($sut->getColor($globalActivity));

        $globalActivity->setColor('#000001');
        self::assertEquals('#000001', $sut->getColor($globalActivity));

        $customer = new Customer();
        self::assertNull($sut->getColor($customer));

        $customer->setColor('#000004');
        self::assertEquals('#000004', $sut->getColor($customer));

        $project = new Project();
        self::assertNull($sut->getColor($project));

        $project->setCustomer($customer);
        self::assertEquals('#000004', $sut->getColor($project));

        $project->setColor('#000003');
        self::assertEquals('#000003', $sut->getColor($project));

        $activity = new Activity();
        self::assertNull($sut->getColor($activity));

        $timesheet = new Timesheet();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        self::assertEquals('#000003', $sut->getColor($timesheet));
        self::assertEquals('#000003', $sut->getTimesheetColor($timesheet));

        $activity->setProject($project);
        self::assertEquals('#000003', $sut->getColor($activity));

        $timesheet = new Timesheet();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        self::assertEquals('#000003', $sut->getColor($timesheet));
        self::assertEquals('#000003', $sut->getTimesheetColor($timesheet));

        $activity->setColor('#000002');
        self::assertEquals('#000002', $sut->getColor($activity));

        $timesheet = new Timesheet();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        self::assertEquals('#000002', $sut->getColor($timesheet));
        self::assertEquals('#000002', $sut->getTimesheetColor($timesheet));

        $timesheet = new Timesheet();
        self::assertEquals(Constants::DEFAULT_COLOR, $sut->getTimesheetColor($timesheet));
        self::assertNull($sut->getColor($timesheet));
        self::assertEquals(Constants::DEFAULT_COLOR, $sut->getColor($timesheet, true));

        $timesheet = new Timesheet();
        $timesheet->setActivity(new Activity());
        $project = new Project();
        $customer = new Customer();
        $customer->setColor('#123456');
        $project->setCustomer($customer);
        $timesheet->setProject($project);
        self::assertEquals('#123456', $sut->getColor($timesheet, true));
    }

    public function testGetFontContrastColor()
    {
        $sut = new Color();
        $this->assertEquals('#ffffff', $sut->getFontContrastColor('#666'));
        $this->assertEquals('#ffffff', $sut->getFontContrastColor('#666666'));
        $this->assertEquals('#ffffff', $sut->getFontContrastColor('#000000'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#ccc'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#cccccc'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#ffffff'));
    }

    public function testGetFontContrastColorReturnsContrastForDefaultColorOnInvalidColor()
    {
        $sut = new Color();
        $this->assertEquals('#000000', $sut->getFontContrastColor(''));
        $this->assertEquals('#000000', $sut->getFontContrastColor('000000'));
        $this->assertEquals('#000000', $sut->getFontContrastColor(Constants::DEFAULT_COLOR));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#6'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#66'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#6666'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#cccc'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#ccccc'));
        $this->assertEquals('#000000', $sut->getFontContrastColor('#ccccccc'));
    }
}
