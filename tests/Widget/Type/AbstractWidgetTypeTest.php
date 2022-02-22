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

    protected function assertDefaultData(AbstractWidgetType $sut)
    {
        self::assertNull($sut->getData());
    }

    public function testDefaultData()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut);
        self::assertEquals($this->getDefaultOptions(), $sut->getOptions());
        $this->assertDefaultData($sut);
        self::assertEquals('bar', $sut->getOption('foo', 'bar'));
    }

    public function testFluentInterface()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setOptions([]));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setId(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setTitle(''));
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
        $options = $sut->getOptions();
        self::assertEquals('blab', $options['blub']);
        self::assertEquals('money', $options['dataType']);
        self::assertEquals('trääääää', $options['föööö']);
    }

    public function testData()
    {
        $sut = $this->createSut();

        self::assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));

        $sut->setData('slkudfhalksjdhfkljsahdf');
        self::assertEquals('slkudfhalksjdhfkljsahdf', $sut->getData());

        $data = new \stdClass();
        $sut->setData($data);
        self::assertSame($data, $sut->getData());
    }
}
