<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget;

use App\Tests\Widget\Type\More;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\WidgetService
 */
class WidgetServiceTest extends TestCase
{
    public function testConstruct()
    {
        $sut = new WidgetService();
        self::assertFalse($sut->hasWidget('sdfsdf'));
    }

    public function testHasAndGetWidget()
    {
        $widget = new More();
        $widget->setId('sdfsdf');

        $sut = new WidgetService();
        $sut->registerWidget($widget);
        self::assertTrue($sut->hasWidget('sdfsdf'));
        self::assertSame($widget, $sut->getWidget('sdfsdf'));
    }
}
