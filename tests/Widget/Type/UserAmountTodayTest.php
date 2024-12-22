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
use App\Widget\Type\AbstractUserRevenuePeriod;
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\UserAmountToday;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Widget\Type\UserAmountToday
 * @covers \App\Widget\Type\AbstractUserRevenuePeriod
 */
class UserAmountTodayTest extends AbstractWidgetTestCase
{
    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0.0, $sut->getData());
    }

    /**
     * @return AbstractUserRevenuePeriod
     */
    public function createSut(): AbstractWidget
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $widget = new UserAmountToday($repository, $dispatcher);
        $widget->setUser(new User());

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_TODAY,
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter-money.html.twig', $sut->getTemplateName());
        self::assertEquals('UserAmountToday', $sut->getId());
    }
}
