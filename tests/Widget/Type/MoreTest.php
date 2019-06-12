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
 */
class MoreTest extends AbstractWidgetTypeTest
{
    public function createSut(): AbstractWidgetType
    {
        return new More();
    }

    public function testExtendsSimpleWidget()
    {
        $sut = $this->createSut();
        $this->assertInstanceOf(SimpleWidget::class, $sut);
    }
}
