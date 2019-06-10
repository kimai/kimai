<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Repository\TimesheetRepository;
use App\Repository\WidgetRepository;
use App\Widget\Type\SimpleStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\WidgetRepository
 */
class WidgetRepositoryTest extends TestCase
{
    public function testHasWidget()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, ['test' => []]);

        $this->assertFalse($sut->has('foo'));
        $this->assertTrue($sut->has('test'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot find widget: foo
     */
    public function testGetWidgetThrowsExceptionOnNonExistingWidget()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, ['test' => []]);
        $sut->get('foo', null);
    }

    /**
     * @dataProvider getWidgetData
     */
    public function testGetWidget($data, $query, $dataType)
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $repoMock->method('getStatistic')->willReturn($data);

        $widget = [
            'color' => 'sunny',
            'icon' => 'far fa-test',
            'user' => false,
            'begin' => null,
            'end' => null,
            'query' => $query,
            'title' => 'Test widget',
        ];

        $sut = new WidgetRepository($repoMock, ['test' => $widget]);
        $widget = $sut->get('test', null);

        $this->assertEquals('Test widget', $widget->getTitle());
        $this->assertEquals($data, $widget->getData());
        $this->assertEquals('sunny', $widget->getOption('color'));
        $this->assertEquals('far fa-test', $widget->getOption('icon'));
        $this->assertEquals($dataType, $widget->getOption('dataType'));
    }

    public function getWidgetData()
    {
        return [
            [12, TimesheetRepository::STATS_QUERY_DURATION, SimpleStatistic::DATA_TYPE_DURATION],
            [112233, TimesheetRepository::STATS_QUERY_AMOUNT, 'int'],
            [37, TimesheetRepository::STATS_QUERY_ACTIVE, 'int'],
            [375, TimesheetRepository::STATS_QUERY_RATE, SimpleStatistic::DATA_TYPE_MONEY],
            [['test' => 'foo'], TimesheetRepository::STATS_QUERY_USER, 'int'],
        ];
    }
}
