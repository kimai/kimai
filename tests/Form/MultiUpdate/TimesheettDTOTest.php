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
use App\Entity\User;
use App\Form\MultiUpdate\TimesheetDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\MultiUpdate\TimesheetDTO
 */
class TimesheettDTOTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new TimesheetDTO();
        self::assertEmpty($sut->getEntities());
        self::assertEquals(['' => ''], $sut->getActions());
        self::assertNull($sut->getAction());

        self::assertNull($sut->isExported());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getAction());
        self::assertNull($sut->getCustomer());
        self::assertEquals([], $sut->getTags());
        self::assertNull($sut->getUser());
    }

    public function testSetterAndGetter()
    {
        $sut = new TimesheetDTO();

        self::assertInstanceOf(TimesheetDTO::class, $sut->addUpdate('foo'));
        self::assertInstanceOf(TimesheetDTO::class, $sut->addDelete('bar'));
        self::assertInstanceOf(TimesheetDTO::class, $sut->addAction('test', 'hello/world'));
        self::assertEquals(
            [
                '' => '',
                'action.edit' => 'foo',
                'action.delete' => 'bar',
                'test' => 'hello/world'
            ],
            $sut->getActions()
        );

        self::assertInstanceOf(TimesheetDTO::class, $sut->setAction('sdfsdfsdf'));
        self::assertEquals('sdfsdfsdf', $sut->getAction());

        self::assertInstanceOf(TimesheetDTO::class, $sut->setEntities([1, 2, 3, 4, 5, 6, 7, 8, 9, '0815']));
        self::assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, '0815'], $sut->getEntities());

        self::assertInstanceOf(TimesheetDTO::class, $sut->setExported(true));
        self::assertTrue($sut->isExported());
        self::assertInstanceOf(TimesheetDTO::class, $sut->setExported(false));
        self::assertFalse($sut->isExported());

        self::assertInstanceOf(TimesheetDTO::class, $sut->setTags(['foo', '0815']));
        self::assertEquals(['foo', '0815'], $sut->getTags());

        $user = (new User())->setUsername('sdfsdfsd');
        self::assertInstanceOf(TimesheetDTO::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());

        $activity = (new Activity())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetDTO::class, $sut->setActivity($activity));
        self::assertSame($activity, $sut->getActivity());

        $project = (new Project())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetDTO::class, $sut->setProject($project));
        self::assertSame($project, $sut->getProject());

        $customer = (new Customer())->setName('sdfsdfsd');
        self::assertInstanceOf(TimesheetDTO::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());
    }
}
