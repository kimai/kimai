<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\TotalsUser;

/**
 * @covers \App\Widget\Type\TotalsUser
 */
class TotalsUserTest extends AbstractWidgetTestCase
{
    /** @var User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $user = new User();
        $user->setAlias('foo');

        $this->user = $user;
    }

    public function createSut(): TotalsUser
    {
        return $this->createWidget();
    }

    private function createWidget(int $results = 1): TotalsUser
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->any())->method('countUsersForQuery')->willReturn($results);

        $widget = new TotalsUser($repository);
        $widget->setUser($this->user);

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'route' => 'admin_user',
            'icon' => 'user',
            'color' => 'red',
        ];
    }

    protected function assertDefaultData(AbstractWidgetType $sut): void
    {
        self::assertEquals(1, $sut->getData());
    }

    public function testData(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = $this->createWidget(99);
        self::assertEquals('widget/widget-more.html.twig', $sut->getTemplateName());
        $sut->setUser($user);

        self::assertEquals(['view_user'], $sut->getPermissions());
        self::assertEquals(99, $sut->getData([]));
    }
}
