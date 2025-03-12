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
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\RevenueUser;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Widget\Type\RevenueUser
 */
class RevenueUserTest extends AbstractWidgetTestCase
{
    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0.0, $sut->getData());
    }

    public function createSut(): RevenueUser
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $configuration = SystemConfigurationFactory::createStub();

        $widget = new RevenueUser($repository, $configuration, $dispatcher);
        $widget->setUser(new User());

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return ['daterange' => 'month'];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-revenue.html.twig', $sut->getTemplateName());
        self::assertEquals('RevenueUser', $sut->getId());
    }
}
