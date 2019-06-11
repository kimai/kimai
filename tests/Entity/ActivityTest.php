<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Activity
 */
class ActivityTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Activity();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getProject());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getComment());
        $this->assertTrue($sut->getVisible());
        self::assertIsIterable($sut->getTimesheets());
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
        $this->assertNull($sut->getColor());
        $this->assertEquals(0.0, $sut->getBudget());
        $this->assertEquals(0, $sut->getTimeBudget());
    }

    public function testSetterAndGetter()
    {
        $sut = new Activity();
        $this->assertInstanceOf(Activity::class, $sut->setName('foo-bar'));
        $this->assertEquals('foo-bar', $sut->getName());
        $this->assertEquals('foo-bar', (string) $sut);

        $this->assertInstanceOf(Activity::class, $sut->setVisible(false));
        $this->assertFalse($sut->getVisible());

        $this->assertInstanceOf(Activity::class, $sut->setComment('hello world'));
        $this->assertEquals('hello world', $sut->getComment());

        $this->assertInstanceOf(Activity::class, $sut->setColor('#fffccc'));
        $this->assertEquals('#fffccc', $sut->getColor());

        $this->assertInstanceOf(Activity::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());

        $this->assertInstanceOf(Activity::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());

        $this->assertInstanceOf(Activity::class, $sut->setBudget(12345.67));
        $this->assertEquals(12345.67, $sut->getBudget());

        $this->assertInstanceOf(Activity::class, $sut->setTimeBudget(937321));
        $this->assertEquals(937321, $sut->getTimeBudget());
    }
}
