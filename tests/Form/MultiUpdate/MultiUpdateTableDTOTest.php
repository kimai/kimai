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
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Form\MultiUpdate\MultiUpdateTableDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\MultiUpdate\MultiUpdateTableDTO
 */
class MultiUpdateTableDTOTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new MultiUpdateTableDTO();
        self::assertEmpty($sut->getEntities());
        self::assertEquals(['' => ''], $sut->getActions());
        self::assertNull($sut->getAction());
    }

    public function testSetterAndGetter()
    {
        $sut = new MultiUpdateTableDTO();

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addUpdate('foo'));
        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addDelete('bar'));
        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addAction('test', 'hello/world'));
        self::assertEquals(
            [
                '' => '',
                'action.edit' => 'foo',
                'action.delete' => 'bar',
                'test' => 'hello/world'
            ],
            $sut->getActions()
        );

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->setAction('sdfsdfsdf'));
        self::assertEquals('sdfsdfsdf', $sut->getAction());

        $activity = new Activity();
        $project = new Project();
        $customer = new Customer();
        $timesheet = new Timesheet();
        $tag = new Tag();

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->setEntities([$tag, $timesheet, $activity, $customer, $project]));
        self::assertEquals([$tag, $timesheet, $activity, $customer, $project], $sut->getEntities());
    }
}
