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
use App\Widget\Type\AbstractCounterYear;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\UserAmountYear;
use App\Widget\WidgetInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Widget\Type\UserAmountYear
 * @covers \App\Widget\Type\AbstractCounterYear
 */
class UserAmountYearTest extends AbstractWidgetTypeTest
{
    protected function assertDefaultData(AbstractWidgetType $sut): void
    {
        self::assertEquals([], $sut->getData());
    }

    /**
     * @return AbstractCounterYear
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getStatistic')->willReturn([]);
        $configuration = SystemConfigurationFactory::createStub();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $widget = new UserAmountYear($repository, $configuration, $dispatcher);
        $widget->setUser(new User());

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter-money.html.twig', $sut->getTemplateName());
        self::assertEquals('UserAmountYear', $sut->getId());
    }
}
