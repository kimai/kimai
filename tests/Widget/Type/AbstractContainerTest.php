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

        self::assertInstanceOf(AbstractContainer::class, $sut);

        self::assertEquals('', $sut->getId());
        self::assertEquals('', $sut->getTitle());

        self::assertEquals(0, $sut->getOrder());
        self::assertEquals([], $sut->getOptions());
        self::assertEquals([], $sut->getWidgets());
        self::assertEquals([], $sut->getData());
    }

    public function testSetter()
    {
        $widget = (new More())->setTitle('bar')->setId('foo');

        $sut = $this->createSut();
        $sut->setTitle('hello-world');
        $sut->setOrder(13);
        $sut->addWidget($widget);

        self::assertEquals('hello-world', $sut->getTitle());
        self::assertEquals('hello-world', $sut->getId());

        self::assertCount(1, $sut->getWidgets());
        self::assertCount(1, $sut->getData());
        self::assertEquals([$widget], $sut->getWidgets());
        self::assertEquals([$widget], $sut->getData());

        self::assertEquals(13, $sut->getOrder());
        self::assertEquals('bar', $sut->getWidgets()[0]->getTitle());
    }

    public function testSetOptionNotImplemented()
    {
        $this->expectException(\BadMethodCallException::class);

        $sut = $this->createSut();
        $sut->setOption('dfsdf', []);
    }
}
