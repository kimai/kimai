<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\CompoundRow;
use App\Widget\Type\More;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\CompoundRow
 */
class DashboardSectionTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new CompoundRow();
        $sut->setTitle('test');
        $this->assertEquals('test', $sut->getTitle());
        $this->assertEquals(0, $sut->getOrder());
        $this->assertEquals([], $sut->getWidgets());
    }

    public function testSetter()
    {
        $sut = new CompoundRow();
        $sut->setTitle('hello-world');
        $sut->setOrder(13);
        $sut->addWidget((new More())->setTitle('bar')->setId('foo'));

        $this->assertCount(1, $sut->getWidgets());
        $this->assertEquals(13, $sut->getOrder());
        $this->assertEquals('bar', $sut->getWidgets()[0]->getTitle());
    }
}
