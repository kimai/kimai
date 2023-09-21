<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\TotalsActivity;

/**
 * @covers \App\Widget\Type\TotalsActivity
 */
class TotalsActivityTest extends AbstractWidgetTest
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

    public function createSut(): TotalsActivity
    {
        return $this->createWidget();
    }

    private function createWidget(int $results = 1): TotalsActivity
    {
        $repository = $this->createMock(ActivityRepository::class);
        $repository->expects($this->any())->method('countActivitiesForQuery')->willReturn($results);

        $widget = new TotalsActivity($repository);
        $widget->setUser($this->user);

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'route' => 'admin_activity',
            'icon' => 'activity',
            'color' => 'red',
        ];
    }

    protected function assertDefaultData(AbstractWidgetType $sut): void
    {
        self::assertEquals(1, $sut->getData());
    }

    public function testData()
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = $this->createWidget(99);
        self::assertEquals('widget/widget-more.html.twig', $sut->getTemplateName());
        $sut->setUser($user);

        self::assertEquals(['view_activity', 'view_teamlead_activity', 'view_team_activity'], $sut->getPermissions());
        self::assertEquals(99, $sut->getData([]));
    }
}
