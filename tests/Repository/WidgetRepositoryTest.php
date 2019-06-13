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
use App\Security\CurrentUser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\WidgetRepository
 */
class WidgetRepositoryTest extends TestCase
{
    public function testHasWidget()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => []]);

        $this->assertFalse($sut->has('foo'));
        $this->assertTrue($sut->has('test'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot find widget "foo".
     */
    public function testGetWidgetThrowsExceptionOnNonExistingWidget()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => []]);
        $sut->get('foo');
    }

    /**
     * @expectedException \App\Widget\WidgetException
     * @expectedExceptionMessage Unknown widget type "\App\Widget\Type\FooBar"
     */
    public function testGetWidgetThrowsExceptionOnInvalidType()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => ['type' => 'FooBar', 'user' => false]]);
        $sut->get('test');
    }

    /**
     * @expectedException \App\Widget\WidgetException
     * @expectedExceptionMessage Invalid widget type "\App\Widget\Type\CompoundChart" does not extend AbstractWidgetType
     */
    public function testGetWidgetTriggersExceptionOnWrongClass()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => ['type' => 'CompoundChart', 'user' => false]]);
        $sut->get('test');
    }

    /**
     * @dataProvider getWidgetData
     */
    public function testGetWidget($data, $query, $dataType)
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $repoMock->method('getStatistic')->willReturn($data);

        $userMock = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $widget = [
            'color' => 'sunny',
            'icon' => 'far fa-test',
            'user' => false,
            'begin' => null,
            'end' => null,
            'query' => $query,
            'title' => 'Test widget',
        ];

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => $widget]);
        $widget = $sut->get('test');

        $options = $widget->getOptions();
        $this->assertEquals('Test widget', $widget->getTitle());
        $this->assertEquals($data, $widget->getData());
        $this->assertEquals('sunny', $options['color']);
        $this->assertEquals('far fa-test', $options['icon']);
        $this->assertEquals($dataType, $options['dataType']);
    }

    public function getWidgetData()
    {
        return [
            [12, TimesheetRepository::STATS_QUERY_DURATION, 'duration'],
            [112233, TimesheetRepository::STATS_QUERY_AMOUNT, 'int'],
            [37, TimesheetRepository::STATS_QUERY_ACTIVE, 'int'],
            [375, TimesheetRepository::STATS_QUERY_RATE, 'money'],
            [['test' => 'foo'], TimesheetRepository::STATS_QUERY_USER, 'int'],
        ];
    }
}
