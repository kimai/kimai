<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Widget\Type\DailyWorkingTimeChart;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\DailyWorkingTimeChart
 * @covers \App\Widget\Type\AbstractWidgetType
 * @covers \App\Repository\TimesheetRepository
 */
class DailyWorkingTimeChartTest extends TestCase
{
    public function createSut(): DailyWorkingTimeChart
    {
        $repository = $this->createMock(TimesheetRepository::class);

        $sut = new DailyWorkingTimeChart($repository);
        $sut->setUser(new User());

        return $sut;
    }

    public function testDefaultValues(): void
    {
        $sut = $this->createSut();
        self::assertEquals('DailyWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
        $options = $sut->getOptions();
        self::assertNull($options['begin']);
        self::assertNull($options['end']);
        self::assertEquals('', $options['color']);
    }

    public function testSetter(): void
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals('trääääää', $sut->getOptions()['föööö']);
    }

    public function testGetOptions(): void
    {
        $sut = $this->createSut();

        $options = $sut->getOptions(['type' => 'xxx']);
        self::assertIsString($options['id']);
        self::assertStringStartsWith('DailyWorkingTimeChart_', $options['id']);
        self::assertEquals('xxx', $options['type']);
    }
}
