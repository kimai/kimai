<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Widget\Type\PaginatedWorkingTimeChart;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\PaginatedWorkingTimeChart
 * @covers \App\Widget\Type\AbstractWidgetType
 * @covers \App\Repository\TimesheetRepository
 */
class PaginatedWorkingTimeChartTest extends TestCase
{
    /**
     * @return PaginatedWorkingTimeChart
     */
    public function createSut(): PaginatedWorkingTimeChart
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = $this->createMock(SystemConfiguration::class);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);
        $sut->setUser(new User());

        return $sut;
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();
        self::assertEquals('PaginatedWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
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
        self::assertEquals('bar', $options['type']);
    }

    public function testGetData()
    {
        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(42);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(4711);

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getDailyStats')->willReturnCallback(function ($user, $begin, $end) use ($activity, $project) {
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

        $expectedKeys = [
            'begin', 'end', 'stats', 'thisMonth', 'lastWeekInYear', 'lastWeekInLastYear', 'day', 'week', 'month', 'year', 'financial', 'financialBegin'
        ];

        $configuration = $this->createMock(SystemConfiguration::class);
        $configuration->expects($this->once())->method('getFinancialYearStart')->willReturn(null);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);

        $sut->setUser(new User());
        $data = $sut->getData($sut->getOptions());

        self::assertCount(\count($expectedKeys), $data);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertNull($data['financialBegin']);
    }

    public function testGetDataWithFinancialYear()
    {
        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(42);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(4711);

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getDailyStats')->willReturnCallback(function ($user, $begin, $end) use ($activity, $project) {
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

        $expectedKeys = [
            'begin', 'end', 'stats', 'thisMonth', 'lastWeekInYear', 'lastWeekInLastYear', 'day', 'week', 'month', 'year', 'financial', 'financialBegin'
        ];

        $configuration = $this->createMock(SystemConfiguration::class);
        $configuration->expects($this->once())->method('getFinancialYearStart')->willReturn('2020-01-01');

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);

        $sut->setUser(new User());
        $data = $sut->getData($sut->getOptions());

        self::assertCount(\count($expectedKeys), $data);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertInstanceOf(\DateTime::class, $data['financialBegin']);
    }
}
