<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\ActiveUsersYear;
use App\Widget\Type\CounterYear;
use App\Widget\Type\SimpleStatisticChart;

/**
 * @covers \App\Widget\Type\ActiveUsersYear
 * @covers \App\Widget\Type\CounterYear
 */
class ActiveUsersYearTest extends AbstractWidgetTypeTest
{
    /**
     * @return CounterYear
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = $this->createMock(SystemConfiguration::class);

        return new ActiveUsersYear($repository, $configuration);
    }

    public function getDefaultOptions(): array
    {
        return [
            'dataType' => 'int',
            'icon' => 'user',
            'color' => 'yellow',
        ];
    }

    public function testData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set data on instances of SimpleStatisticChart');

        $sut = $this->createSut();
        self::assertInstanceOf(SimpleStatisticChart::class, $sut);
        $sut->setData(10);
    }

    public function testSettings()
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter.html.twig', $sut->getTemplateName());
        self::assertEquals('activeUsersYear', $sut->getId());
    }
}
