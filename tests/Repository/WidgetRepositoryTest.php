<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Model\Widget;
use App\Repository\TimesheetRepository;
use App\Repository\WidgetRepository;
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
        $this->assertEquals('sunny', $widget->getColor());
        $this->assertEquals('far fa-test', $widget->getIcon());
        $this->assertEquals($dataType, $widget->getDataType());
    }

    public function getWidgetData()
    {
        return [
            [12, TimesheetRepository::STATS_QUERY_DURATION, Widget::DATA_TYPE_DURATION],
            [112233, TimesheetRepository::STATS_QUERY_AMOUNT, Widget::DATA_TYPE_MONEY],
            [37, TimesheetRepository::STATS_QUERY_ACTIVE, Widget::DATA_TYPE_INT],
            [375, TimesheetRepository::STATS_QUERY_RATE, Widget::DATA_TYPE_INT],
            [['test' => 'foo'], TimesheetRepository::STATS_QUERY_USER, Widget::DATA_TYPE_INT],
        ];
    }
}
