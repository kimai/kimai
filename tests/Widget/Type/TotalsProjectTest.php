<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\TotalsProject;

/**
 * @covers \App\Widget\Type\TotalsProject
 */
class TotalsProjectTest extends AbstractWidgetTest
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

    public function createSut(): TotalsProject
    {
        return $this->createWidget();
    }

    private function createWidget(int $results = 1): TotalsProject
    {
        $repository = $this->createMock(ProjectRepository::class);
        $repository->expects($this->any())->method('countProjectsForQuery')->willReturn($results);

        $widget = new TotalsProject($repository);
        $widget->setUser($this->user);

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'route' => 'admin_project',
            'icon' => 'project',
            'color' => 'red',
        ];
    }

    protected function assertDefaultData(AbstractWidgetType $sut)
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

        self::assertEquals(['view_project', 'view_teamlead_project', 'view_team_project'], $sut->getPermissions());
        self::assertEquals(99, $sut->getData([]));
    }
}
