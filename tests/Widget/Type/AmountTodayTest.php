<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractAmountPeriod;
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\AmountToday;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Widget\Type\AmountToday
 * @covers \App\Widget\Type\AbstractAmountPeriod
 */
class AmountTodayTest extends AbstractWidgetTestCase
{
    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0.0, $sut->getData());
    }

    /**
     * @return AbstractAmountPeriod
     */
    public function createSut(): AbstractWidget
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        return new AmountToday($repository, $dispatcher);
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
        self::assertEquals('AmountToday', $sut->getId());
    }
}
