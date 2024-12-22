<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;

/**
 * @covers \App\Widget\Type\AbstractWidgetType
 */
abstract class AbstractWidgetTypeTestCase extends AbstractWidgetTestCase
{
    abstract public function createSut(): AbstractWidgetType;

    abstract public function getDefaultOptions(): array;

    protected function assertDefaultData(AbstractWidgetType $sut): void
    {
        self::assertNull($sut->getData());
    }

    public function testDefaultData(): void
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut);
        self::assertEquals($this->getDefaultOptions(), $sut->getOptions());
        $this->assertDefaultData($sut);
    }

    public function testSetter(): void
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals(array_merge($this->getDefaultOptions(), ['föööö' => 'trääääää']), $sut->getOptions());

        $sut->setOptions(['blub' => 'blab', 'dataType' => 'money']);
        $options = $sut->getOptions();
        self::assertEquals('blab', $options['blub']);
        self::assertEquals('money', $options['dataType']);
        self::assertEquals('trääääää', $options['föööö']);
    }
}
