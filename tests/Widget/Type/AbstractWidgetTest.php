<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\AbstractWidget
 */
abstract class AbstractWidgetTest extends TestCase
{
    abstract public function createSut(): AbstractWidget;

    abstract public function getDefaultOptions(): array;

    public function testDefaultData()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidget::class, $sut);
        self::assertEquals($this->getDefaultOptions(), $sut->getOptions());
    }

    public function testSetter()
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals(array_merge($this->getDefaultOptions(), ['föööö' => 'trääääää']), $sut->getOptions());

        $sut->setOption('blub', 'blab');
        $sut->setOption('dataType', 'money');
        $options = $sut->getOptions();
        self::assertEquals('blab', $options['blub']);
        self::assertEquals('money', $options['dataType']);
        self::assertEquals('trääääää', $options['föööö']);
    }
}
