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
use App\Widget\Type\ActiveUsers;

/**
 * @covers \App\Widget\Type\ActiveUsers
 */
class ActiveUsersTest extends AbstractWidgetTestCase
{
    /**
     * @return ActiveUsers
     */
    public function createSut(): AbstractWidget
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $configuration = SystemConfigurationFactory::createStub();

        $sut = new ActiveUsers($repository, $configuration);
        $sut->setUser(new User());

        return $sut;
    }

    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0, $sut->getData());
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-active-users.html.twig', $sut->getTemplateName());
        self::assertEquals('ActiveUsers', $sut->getId());
    }
}
