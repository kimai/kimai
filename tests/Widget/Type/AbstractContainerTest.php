<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractContainer;
use App\Widget\Type\More;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\AbstractContainer
 */
abstract class AbstractContainerTest extends TestCase
{
    abstract public function createSut(): AbstractContainer;

    public function testDefaultValues()
    {
        $sut = $this->createSut();

        $this->assertInstanceOf(AbstractContainer::class, $sut);

        $this->assertEquals('', $sut->getId());
        $this->assertEquals('', $sut->getTitle());

        $this->assertEquals(0, $sut->getOrder());
        $this->assertEquals([], $sut->getOptions());
        $this->assertEquals([], $sut->getWidgets());
        $this->assertEquals([], $sut->getData());
    }

    public function testSetter()
    {
        $widget = (new More())->setTitle('bar')->setId('foo');

        $sut = $this->createSut();
        $sut->setTitle('hello-world');
        $sut->setOrder(13);
        $sut->addWidget($widget);

        $this->assertEquals('hello-world', $sut->getTitle());
        $this->assertEquals('hello-world', $sut->getId());

        $this->assertCount(1, $sut->getWidgets());
        $this->assertCount(1, $sut->getData());
        $this->assertEquals([$widget], $sut->getWidgets());
        $this->assertEquals([$widget], $sut->getData());

        $this->assertEquals(13, $sut->getOrder());
        $this->assertEquals('bar', $sut->getWidgets()[0]->getTitle());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testSetOptionNotImplemented()
    {
        $sut = $this->createSut();
        $this->assertInstanceOf(AbstractContainer::class, $sut->setOption('dfsdf', []));
    }
}
