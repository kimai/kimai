<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use App\Model\Statistic\Day;
use App\Widget\DataProvider\DailyWorkingTimeChartProvider;
use App\Widget\Type\DailyWorkingTimeChart;
use App\Widget\WidgetInterface;
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
        $repository = $this->createMock(DailyWorkingTimeChartProvider::class);

        $sut = new DailyWorkingTimeChart($repository);
        $sut->setUser(new User());

        return $sut;
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(WidgetInterface::class, $sut);
        self::assertEquals('DailyWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
        $options = $sut->getOptions();
        self::assertNull($options['begin']);
        self::assertNull($options['end']);
        self::assertEquals('', $options['color']);
    }

    public function testSetter()
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals('trääääää', $sut->getOptions()['föööö']);
    }

    public function testGetOptions()
    {
        $sut = $this->createSut();

        $options = $sut->getOptions(['type' => 'xxx']);
        self::assertStringStartsWith('DailyWorkingTimeChart_', $options['id']);
        self::assertEquals('xxx', $options['type']);
    }

    public function testGetData()
    {
        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(42);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(4711);

        $repository = $this->getMockBuilder(DailyWorkingTimeChartProvider::class)->disableOriginalConstructor()->onlyMethods(['getDailyData'])->getMock();
        $repository->expects($this->once())->method('getDailyData')->willReturnCallback(function ($begin, $end, $user) use ($activity, $project) {
            return [
                [
                    'year' => $begin->format('Y'),
                    'month' => $begin->format('n'),
                    'day' => $begin->format('j'),
                    'rate' => 13.75,
                    'duration' => 1234,
                    'billable' => 1234,
                    'details' => [
                        [
                            'activity' => $activity,
                            'project' => $project,
                            'billable' => 1234,
                        ]
                    ]
                ]
            ];
        });

        $sut = new DailyWorkingTimeChart($repository);
        $sut->setUser(new User());
        $data = $sut->getData($sut->getOptions());
        self::assertCount(2, $data);
        self::assertArrayHasKey('activities', $data);
        self::assertArrayHasKey('data', $data);

        self::assertCount(1, $data['activities']);
        self::assertArrayHasKey('4711_42', $data['activities']);
        self::assertCount(2, $data['activities']['4711_42']);
        self::assertArrayHasKey('activity', $data['activities']['4711_42']);
        self::assertArrayHasKey('project', $data['activities']['4711_42']);
        self::assertSame($activity, $data['activities']['4711_42']['activity']);
        self::assertSame($project, $data['activities']['4711_42']['project']);

        self::assertCount(7, $data['data']);
        foreach ($data['data'] as $statObj) {
            self::assertInstanceOf(Day::class, $statObj);
        }
    }
}
