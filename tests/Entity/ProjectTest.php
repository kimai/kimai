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
use App\Entity\Timesheet;

/**
 * @covers \App\Entity\Project
 */
class ProjectTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Project();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getCustomer());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getOrderNumber());
        $this->assertNull($sut->getComment());
        $this->assertTrue($sut->getVisible());
        $this->assertEquals(0.0, $sut->getBudget());
        // activities
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
        $this->assertNull($sut->getTimesheets());
        $this->assertNull($sut->getColor());
    }

    public function testSetterAndGetter()
    {
        $sut = new Project();

        $customer = (new Customer())->setName('customer');
        $this->assertInstanceOf(Project::class, $sut->setCustomer($customer));
        $this->assertSame($customer, $sut->getCustomer());

        $this->assertInstanceOf(Project::class, $sut->setName('123456789'));
        $this->assertEquals('123456789', (string) $sut);

        $this->assertInstanceOf(Project::class, $sut->setOrderNumber('123456789'));
        $this->assertEquals('123456789', $sut->getOrderNumber());

        $this->assertInstanceOf(Project::class, $sut->setComment('a comment'));
        $this->assertEquals('a comment', $sut->getComment());

        $this->assertInstanceOf(Project::class, $sut->setColor('#fffccc'));
        $this->assertEquals('#fffccc', $sut->getColor());

        $this->assertInstanceOf(Project::class, $sut->setVisible(false));
        $this->assertFalse($sut->getVisible());

        $this->assertInstanceOf(Project::class, $sut->setBudget(12345.67));
        $this->assertEquals(12345.67, $sut->getBudget());

        $activities = [(new Activity())->setName('foo')];
        $this->assertInstanceOf(Project::class, $sut->setActivities($activities));
        $this->assertSame($activities, $sut->getActivities());

        $this->assertInstanceOf(Project::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Project::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());

        $timesheets = [(new Timesheet())->setDescription('foo'), (new Timesheet())->setDescription('bar')];
        $this->assertInstanceOf(Project::class, $sut->setTimesheets($timesheets));
        $this->assertSame($timesheets, $sut->getTimesheets());
    }
}
