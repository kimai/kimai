<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\DashboardSection;
use App\Model\Widget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\DashboardSection
 */
class DashboardSectionTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new DashboardSection('test');
        $this->assertEquals('test', $sut->getTitle());
        $this->assertEquals(0, $sut->getOrder());
        $this->assertEquals([], $sut->getWidgets());
        $this->assertEquals(DashboardSection::TYPE_SIMPLE, $sut->getType());
    }

    public function testSetter()
    {
        $sut = new DashboardSection('hello-world');
        $sut->setType(DashboardSection::TYPE_CHART);
        $sut->setOrder(13);
        $sut->addWidget(new Widget('bar', []));

        $this->assertCount(1, $sut->getWidgets());
        $this->assertEquals(DashboardSection::TYPE_CHART, $sut->getType());
        $this->assertEquals(13, $sut->getOrder());
        $this->assertEquals('bar', $sut->getWidgets()[0]->getTitle());
    }
}
