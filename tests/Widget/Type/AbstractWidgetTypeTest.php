<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\AbstractWidgetType
 */
abstract class AbstractWidgetTypeTest extends TestCase
{
    abstract public function createSut(): AbstractWidgetType;
    abstract public function getDefaultOptions(): array;

    public function testDefaultValues()
    {
        $sut = $this->createSut();
        $this->assertInstanceOf(AbstractWidgetType::class, $sut);
        $this->assertEquals('', $sut->getId());
        $this->assertEquals('', $sut->getTitle());
        $this->assertEquals($this->getDefaultOptions(), $sut->getOptions());
        $this->assertNull($sut->getData());
        $this->assertEquals('bar', $sut->getOption('foo', 'bar'));
    }

    public function testFluentInterface()
    {
        $sut = $this->createSut();
        $this->assertInstanceOf(AbstractWidgetType::class, $sut->setOptions([]));
        $this->assertInstanceOf(AbstractWidgetType::class, $sut->setId(''));
        $this->assertInstanceOf(AbstractWidgetType::class, $sut->setTitle(''));
        $this->assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));
    }

    public function testTitleViaOptionsFallback()
    {
        $sut = $this->createSut();
        $sut->setTitle('bar');
        $this->assertEquals('bar', $sut->getTitle());
        $sut->setTitle('');
        $this->assertEquals('', $sut->getTitle());
        $sut->setOption('title', 'fooooo');
        $this->assertEquals('fooooo', $sut->getTitle());
    }

    public function testSetter()
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        $this->assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));
        $this->assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));
        $this->assertEquals(array_merge($this->getDefaultOptions(), ['föööö' => 'trääääää']), $sut->getOptions());

        $sut->setOptions(['blub' => 'blab', 'dataType' => 'money']);
        $this->assertEquals(['blub' => 'blab', 'dataType' => 'money', 'föööö' => 'trääääää'], $sut->getOptions());

        // id
        $sut->setId('cvbnmyx');
        $this->assertEquals('cvbnmyx', $sut->getId());

        // data
        $sut->setData('slkudfhalksjdhfkljsahdf');
        $this->assertEquals('slkudfhalksjdhfkljsahdf', $sut->getData());

        $data = new \stdClass();
        $sut->setData($data);
        $this->assertSame($data, $sut->getData());
    }
}
