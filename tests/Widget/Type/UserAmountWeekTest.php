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
use App\Widget\Type\AbstractUserAmountPeriod;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\SimpleStatisticChart;
use App\Widget\Type\UserAmountWeek;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Widget\Type\UserAmountWeek
 * @covers \App\Widget\Type\AbstractUserAmountPeriod
 */
class UserAmountWeekTest extends AbstractWidgetTypeTest
{
    protected function assertDefaultData(AbstractWidgetType $sut)
    {
        self::assertEquals(0.0, $sut->getData());
    }

    /**
     * @return AbstractUserAmountPeriod
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $widget = new UserAmountWeek($repository, $dispatcher);
        $widget->setUser(new User());

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'dataType' => 'money',
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_WEEK,
        ];
    }

    public function testData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set data on instances of SimpleStatisticChart');

        $sut = $this->createSut();
        self::assertInstanceOf(SimpleStatisticChart::class, $sut);
        $sut->setData(10);
    }

    public function testSettings()
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter.html.twig', $sut->getTemplateName());
        self::assertEquals('userAmountWeek', $sut->getId());
    }
}
