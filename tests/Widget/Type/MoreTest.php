<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\More;
use App\Widget\Type\SimpleWidget;

/**
 * @covers \App\Widget\Type\More
 * @covers \App\Widget\Type\SimpleWidget
 */
class MoreTest extends AbstractWidgetTypeTest
{
    public function createSut(): AbstractWidgetType
    {
        return new More();
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
        $sut = new More();
        self::assertEquals('widget/widget-more.html.twig', $sut->getTemplateName());
    }
}
