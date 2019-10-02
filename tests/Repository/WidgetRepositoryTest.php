<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Repository\WidgetRepository;
use App\Tests\Mocks\Security\CurrentUserFactory;
use App\Widget\Type\CompoundChart;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\WidgetRepository
 */
class WidgetRepositoryTest extends TestCase
{
    public function testHasWidget()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = (new CurrentUserFactory($this))->create(new User());

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
        $userMock = (new CurrentUserFactory($this))->create(new User());

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => []]);
        $sut->get('foo');
    }

    /**
     * @expectedException \App\Widget\WidgetException
     * @expectedExceptionMessage Unknown widget type "FooBar"
     */
    public function testGetWidgetThrowsExceptionOnInvalidType()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = (new CurrentUserFactory($this))->create(new User());

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => ['type' => 'FooBar', 'user' => false]]);
        $sut->get('test');
    }

    /**
     * @expectedException \App\Widget\WidgetException
     * @expectedExceptionMessage Widget type "App\Widget\Type\CompoundChart" is not an instance of "App\Widget\Type\AbstractWidgetType"
     */
    public function testGetWidgetTriggersExceptionOnWrongClass()
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $userMock = (new CurrentUserFactory($this))->create(new User());

        $sut = new WidgetRepository($repoMock, $userMock, ['test' => ['type' => CompoundChart::class, 'user' => false]]);
        $sut->get('test');
    }

    /**
     * @dataProvider getWidgetData
     */
    public function testGetWidget($data, $query, $dataType)
    {
        $repoMock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $repoMock->method('getStatistic')->willReturn($data);

        $userMock = (new CurrentUserFactory($this))->create(new User());

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
