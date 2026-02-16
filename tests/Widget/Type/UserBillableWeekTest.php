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
use App\Widget\Type\AbstractBillablePercent;
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\UserBillableWeek;
use App\Widget\WidgetInterface;

/**
 * @covers \App\Widget\Type\UserBillableYear
 * @covers \App\Widget\Type\AbstractCounterYear
 */
class UserBillableWeekTest extends AbstractWidgetTypeTest
{
    /**
     * @return AbstractBillablePercent
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = SystemConfigurationFactory::createStub();

        $sut = new UserBillableWeek($repository);
        $sut->setUser(new User());

        return $sut;
    }

    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0, $sut->getData());
    }

    /**
     @return array<mixed>
     */
    public function getDefaultOptions(): array
    {
        return [
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_WEEK,
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-user-billable-percent.html.twig', $sut->getTemplateName());
        self::assertEquals('userBillableWeek', $sut->getId());
    }
}
