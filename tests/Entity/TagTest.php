<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Tag;
use App\Entity\Timesheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Tag
 */
class TagTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Tag();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getColor());
    }

    public function testSetterAndGetter()
    {
        $sut = new Tag();

        $this->assertInstanceOf(Tag::class, $sut->setName('foo'));
        $this->assertEquals('foo', $sut->getName());
        $this->assertEquals('foo', (string) $sut);

        $this->assertInstanceOf(Tag::class, $sut->setColor('#fffccc'));
        $this->assertEquals('#fffccc', $sut->getColor());
    }

    public function testWithTimesheet()
    {
        $sut = new Tag();
        $timesheet = new Timesheet();

        $this->assertEmpty($timesheet->getTags());

        $sut->setName('bar');
        $sut->addTimesheet($timesheet);

        $this->assertSame($sut, $timesheet->getTags()[0]);

        $sut->removeTimesheet($timesheet);
        $this->assertEmpty($timesheet->getTags());
    }
}
