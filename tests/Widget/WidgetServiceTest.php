<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget;

use App\Repository\TimesheetRepository;
use App\Widget\Type\DailyWorkingTimeChart;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\WidgetService
 */
class WidgetServiceTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new WidgetService();
        self::assertFalse($sut->hasWidget('sdfsdf'));
    }

    public function testHasAndGetWidget(): void
    {
        $widget = new DailyWorkingTimeChart($this->createMock(TimesheetRepository::class));

        $sut = new WidgetService();
        $sut->registerWidget($widget);
        self::assertTrue($sut->hasWidget('DailyWorkingTimeChart'));
        self::assertSame($widget, $sut->getWidget('DailyWorkingTimeChart'));
    }
}
