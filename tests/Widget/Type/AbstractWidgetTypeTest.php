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
        self::assertInstanceOf(AbstractWidgetType::class, $sut);
        self::assertEquals('', $sut->getId());
        self::assertEquals('', $sut->getTitle());
        self::assertEquals($this->getDefaultOptions(), $sut->getOptions());
        self::assertNull($sut->getData());
        self::assertEquals('bar', $sut->getOption('foo', 'bar'));
    }

    public function testFluentInterface()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setOptions([]));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setId(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setTitle(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));
    }

    public function testSetter()
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));
        self::assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));
        self::assertEquals(array_merge($this->getDefaultOptions(), ['föööö' => 'trääääää']), $sut->getOptions());

        $sut->setOptions(['blub' => 'blab', 'dataType' => 'money']);
        self::assertEquals(['blub' => 'blab', 'dataType' => 'money', 'föööö' => 'trääääää'], $sut->getOptions());

        // id
        $sut->setId('cvbnmyx');
        self::assertEquals('cvbnmyx', $sut->getId());

        // data
        $sut->setData('slkudfhalksjdhfkljsahdf');
        self::assertEquals('slkudfhalksjdhfkljsahdf', $sut->getData());

        $data = new \stdClass();
        $sut->setData($data);
        self::assertSame($data, $sut->getData());
    }
}
