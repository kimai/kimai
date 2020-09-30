<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\YearChart;

/**
 * @covers \App\Widget\Type\YearChart
 * @covers \App\Widget\Type\SimpleStatisticChart
 * @covers \App\Widget\Type\SimpleWidget
 */
class YearChartTest extends AbstractSimpleStatisticsWidgetTypeTest
{
    public function createSut(): AbstractWidgetType
    {
        $sut = new YearChart($this->createMock(TimesheetRepository::class));
        $sut->setQuery(TimesheetRepository::STATS_QUERY_ACTIVE);

        return $sut;
    }

    public function getDefaultOptions(): array
    {
        return [];
    }
}
