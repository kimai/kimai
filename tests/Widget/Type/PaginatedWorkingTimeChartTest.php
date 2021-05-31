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
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\PaginatedWorkingTimeChart;
use App\Widget\Type\SimpleWidget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\PaginatedWorkingTimeChart
 * @covers \App\Widget\Type\SimpleWidget
 * @covers \App\Widget\Type\AbstractWidgetType
 * @covers \App\Repository\TimesheetRepository
 */
class PaginatedWorkingTimeChartTest extends TestCase
{
    /**
     * @return PaginatedWorkingTimeChart
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = $this->createMock(SystemConfiguration::class);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);
        $sut->setUser(new User());

        return $sut;
    }

    public function testExtendsSimpleWidget()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(SimpleWidget::class, $sut);
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut);
        self::assertEquals('PaginatedWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
        //self::assertNull($sut->getOption('begin', 'xxx'));
//        self::assertNull($sut->getOption('end', 'xxx'));
//        self::assertEquals('', $sut->getOption('color', 'xxx'));
        self::assertInstanceOf(User::class, $sut->getOption('user', 'xxx'));
//        self::assertEquals('bar', $sut->getOption('type', 'xxx'));
    }

    public function testFluentInterface()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setOptions([]));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setId(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setTitle(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));
    }

    public function testSetter()
    {
        $sut = $this->createSut();

        // options
        $sut->setOption('föööö', 'trääääää');
        self::assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));

        // check default values
        self::assertEquals('xxxxx', $sut->getOption('blub', 'xxxxx'));
        self::assertEquals('xxxxx', $sut->getOption('dataType', 'xxxxx'));

        $sut->setOptions(['blub' => 'blab', 'dataType' => 'money']);
        // check option still exists
        self::assertEquals('trääääää', $sut->getOption('föööö', 'tröööö'));
        // check options are now existing
        self::assertEquals('blab', $sut->getOption('blub', 'xxxxx'));
        self::assertEquals('money', $sut->getOption('dataType', 'xxxxx'));

        // id
        $sut->setId('cvbnmyx');
        self::assertEquals('cvbnmyx', $sut->getId());
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
        $data = $sut->getData([]);

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
        $data = $sut->getData([]);

        self::assertCount(\count($expectedKeys), $data);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertInstanceOf(\DateTime::class, $data['financialBegin']);
    }
}
