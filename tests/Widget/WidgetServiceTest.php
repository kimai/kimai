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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WidgetService::class)]
class WidgetServiceTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new WidgetService();
        self::assertFalse($sut->hasWidget('sdfsdf'));
    }

    public function testHasAndGetWidget(): void
    {
        $widget = new More();
        $widget->setId('sdfsdf');

        $sut = new WidgetService();
        $sut->registerWidget($widget);
        self::assertTrue($sut->hasWidget('sdfsdf'));
        self::assertSame($widget, $sut->getWidget('sdfsdf'));
    }
}
