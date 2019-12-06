<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\Counter;
use App\Widget\Type\SimpleWidget;

/**
 * @covers \App\Widget\Type\Counter
 */
class CounterTest extends AbstractWidgetTypeTest
{
    public function createSut(): AbstractWidgetType
    {
        return new Counter();
    }

    public function getDefaultOptions(): array
    {
        return ['dataType' => 'int'];
    }

    public function testExtendsSimpleWidget()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(SimpleWidget::class, $sut);
    }

    public function testTemplateName()
    {
        $sut = new Counter();
        self::assertEquals('widget/widget-counter.html.twig', $sut->getTemplateName());
    }
}
