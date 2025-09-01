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
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\UserDurationBillableYear;

/**
 * @covers \App\Widget\Type\UserDurationBillableYear
 * @covers \App\Widget\Type\AbstractCounterYear
 */
class UserDurationBillableYearTest extends AbstractWidgetTypeTest
{
    /**
     * @return AbstractCounterYear
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = SystemConfigurationFactory::createStub();

        $sut = new UserDurationBillableYear($repository, $configuration);
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
            'icon' => 'duration',
            'color' => 'yellow',
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter-duration.html.twig', $sut->getTemplateName());
        self::assertEquals('userDurationBillableYear', $sut->getId());
    }
}
