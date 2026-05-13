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
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\PaginatedWorkingTimeChart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaginatedWorkingTimeChart::class)]
#[CoversClass(AbstractWidgetType::class)]
#[CoversClass(TimesheetRepository::class)]
class PaginatedWorkingTimeChartTest extends TestCase
{
    public function createSut(): PaginatedWorkingTimeChart
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = SystemConfigurationFactory::createStub();

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);
        $sut->setUser(new User());

        return $sut;
    }

    public function testDefaultValues(): void
    {
        $sut = $this->createSut();
        self::assertEquals('PaginatedWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
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

        $options = $sut->getOptions(['type' => 'xxx', 'period' => 'xxx', 'groupBy' => 'xxx']);
        self::assertEquals('bar', $options['type']);
        self::assertEquals('week', $options['period']);
        self::assertEquals('day', $options['groupBy']);
    }

    public function testGetData(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getDurationForTimeRange')->willReturn(0);

        $expectedKeys = [
            'begin', 'end', 'date', 'dateYear', 'thisMonth', 'lastWeekInYear', 'lastWeekInLastYear', 'period',
            'groupBy', 'type', 'previous', 'next', 'periods', 'groupings', 'current', 'day', 'week', 'month',
            'year', 'financial', 'financialBegin'
        ];

        $configuration = SystemConfigurationFactory::createStub(['company' => ['financial_year' => null]]);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);

        $sut->setUser(new User());
        $data = $sut->getData($sut->getOptions());

        self::assertCount(\count($expectedKeys), $data);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertNull($data['financialBegin']);
        self::assertSame('week', $data['period']);
        self::assertSame('day', $data['groupBy']);
        self::assertSame('bar', $data['type']);
        self::assertCount(6, $data['periods']);
        self::assertCount(2, $data['groupings']);
    }

    public function testGetDataWithFinancialYear(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getDurationForTimeRange')->willReturn(0);

        $expectedKeys = [
            'begin', 'end', 'date', 'dateYear', 'thisMonth', 'lastWeekInYear', 'lastWeekInLastYear', 'period',
            'groupBy', 'type', 'previous', 'next', 'periods', 'groupings', 'current', 'day', 'week', 'month',
            'year', 'financial', 'financialBegin'
        ];

        $configuration = SystemConfigurationFactory::createStub(['company' => ['financial_year' => '2020-01-01']]);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);

        $sut->setUser(new User());
        $data = $sut->getData($sut->getOptions());

        self::assertCount(\count($expectedKeys), $data);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertInstanceOf(\DateTime::class, $data['financialBegin']);
    }

    public function testGetDataWithCustomRange(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getDurationForTimeRange')->willReturn(0);

        $configuration = SystemConfigurationFactory::createStub(['company' => ['financial_year' => null]]);

        $sut = new PaginatedWorkingTimeChart($repository, $configuration);
        $sut->setUser(new User());

        $data = $sut->getData($sut->getOptions([
            'period' => 'custom',
            'groupBy' => 'week',
            'begin' => '2020-02-10',
            'end' => '2020-02-12',
        ]));

        self::assertIsArray($data);
        self::assertSame('custom', $data['period']);
        self::assertSame('week', $data['groupBy']);

        $begin = $data['begin'];
        $end = $data['end'];
        self::assertInstanceOf(\DateTime::class, $begin);
        self::assertInstanceOf(\DateTime::class, $end);
        self::assertSame('2020-02-10', $begin->format('Y-m-d'));
        self::assertSame('2020-02-12', $end->format('Y-m-d'));

        $previous = $data['previous'];
        $next = $data['next'];
        self::assertIsArray($previous);
        self::assertIsArray($next);
        self::assertSame('2020-02-07', $previous['begin']);
        self::assertSame('2020-02-13', $next['begin']);
    }
}
