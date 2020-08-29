<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\SimpleStatisticChart;

/**
 * @covers \App\Widget\Type\SimpleStatisticChart
 */
abstract class AbstractSimpleStatisticsWidgetTypeTest extends AbstractWidgetTypeTest
{
    public function testData()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(SimpleStatisticChart::class, $sut);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set data on instances of SimpleStatisticChart');

        $sut->setData(10);
    }
}
