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
use App\Security\CurrentUser;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\DailyWorkingTimeChart;
use App\Widget\Type\SimpleWidget;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\Type\DailyWorkingTimeChart
 */
class DailyWorkingTimeChartTest extends TestCase
{
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->setMethods(['getUser'])->getMock();
        $user->expects($this->once())->method('getUser')->willReturn(new User());

        return new DailyWorkingTimeChart($repository, $user);
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
        self::assertEquals('DailyWorkingTimeChart', $sut->getId());
        self::assertEquals('stats.yourWorkingHours', $sut->getTitle());
        self::assertEquals('monday this week 00:00:00', $sut->getOption('begin', 'xxx'));
        self::assertEquals('sunday this week 23:59:59', $sut->getOption('end', 'xxx'));
        self::assertEquals('', $sut->getOption('color', 'xxx'));
        self::assertInstanceOf(User::class, $sut->getOption('user', 'xxx'));
        self::assertEquals('bar', $sut->getOption('type', 'xxx'));
        self::assertStringStartsWith('DailyWorkingTimeChart_', $sut->getOption('id', 'xxx'));
    }

    public function testFluentInterface()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setOptions([]));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setId(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setTitle(''));
        self::assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));
    }

    public function testTitleViaOptionsFallback()
    {
        $sut = $this->createSut();
        $sut->setTitle('bar');
        self::assertEquals('bar', $sut->getTitle());
        $sut->setTitle('');
        self::assertEquals('', $sut->getTitle());
        $sut->setOption('title', 'fooooo');
        self::assertEquals('fooooo', $sut->getTitle());
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

    public function testGetData()
    {
        $repository = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->setMethods(['getDailyStats'])->getMock();
        $repository->expects($this->once())->method('getDailyStats')->willReturnCallback(function(User $user, DateTime $begin, DateTime $end) {
            self::assertEquals('tralalala', $user->getUsername());
            return [1,2,3];
        });
        $user = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->setMethods(['getUser'])->getMock();
        $user->expects($this->once())->method('getUser')->willReturn((new User())->setUsername('tralalala'));

        $sut = new DailyWorkingTimeChart($repository, $user);
        $sut->setOption('type', 'xxx');
        $data = $sut->getData();
        self::assertEquals([1,2,3], $data);
        self::assertEquals('bar', $sut->getOption('type', 'yyy'));
    }
}
