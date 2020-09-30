<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\MultiUpdate;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\MultiUpdate\TimesheetMultiUpdateDTO
 */
class TimesheetMultiUpdateDTOTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new TimesheetMultiUpdateDTO();
        self::assertEmpty($sut->getEntities());
        self::assertEquals(['' => ''], $sut->getActions());
        self::assertNull($sut->getAction());

        self::assertNull($sut->isExported());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getAction());
        self::assertNull($sut->getCustomer());
        self::assertEquals([], $sut->getTags());
        self::assertNull($sut->getUser());
        self::assertFalse($sut->isReplaceTags());
        self::assertNull($sut->getFixedRate());
        self::assertNull($sut->getHourlyRate());
    }

    public function testSetterAndGetter()
    {
        $sut = new TimesheetMultiUpdateDTO();

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->addUpdate('foo'));
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->addDelete('bar'));
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->addAction('test', 'hello/world'));
        self::assertEquals(
            [
                '' => '',
                'action.edit' => 'foo',
                'action.delete' => 'bar',
                'test' => 'hello/world'
            ],
            $sut->getActions()
        );

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setAction('sdfsdfsdf'));
        self::assertEquals('sdfsdfsdf', $sut->getAction());

        $entities = [new Timesheet(), new Timesheet(), new Timesheet(), new Timesheet()];

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setEntities($entities));
        self::assertEquals($entities, $sut->getEntities());

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setExported(true));
        self::assertTrue($sut->isExported());
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setExported(false));
        self::assertFalse($sut->isExported());

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setTags(['foo', '0815']));
        self::assertEquals(['foo', '0815'], $sut->getTags());

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setReplaceTags(true));
        self::assertTrue($sut->isReplaceTags());

        $user = (new User())->setUsername('sdfsdfsd');
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());

        $activity = (new Activity())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setActivity($activity));
        self::assertSame($activity, $sut->getActivity());

        $project = (new Project())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setProject($project));
        self::assertSame($project, $sut->getProject());

        $customer = (new Customer())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setFixedRate(12.78));
        self::assertEquals(12.78, $sut->getFixedRate());

        self::assertInstanceOf(TimesheetMultiUpdateDTO::class, $sut->setHourlyRate(123.45));
        self::assertEquals(123.45, $sut->getHourlyRate());
    }
}
