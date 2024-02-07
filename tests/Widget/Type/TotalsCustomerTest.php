<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\TotalsCustomer;

/**
 * @covers \App\Widget\Type\TotalsCustomer
 */
class TotalsCustomerTest extends AbstractWidgetTest
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

    public function createSut(): TotalsCustomer
    {
        return $this->createWidget();
    }

    private function createWidget(int $results = 1): TotalsCustomer
    {
        $repository = $this->createMock(CustomerRepository::class);
        $repository->expects($this->any())->method('countCustomersForQuery')->willReturn($results);

        $widget = new TotalsCustomer($repository);
        $widget->setUser($this->user);

        return $widget;
    }

    public function getDefaultOptions(): array
    {
        return [
            'route' => 'admin_customer',
            'icon' => 'customer',
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

        self::assertEquals(['view_customer', 'view_teamlead_customer', 'view_team_customer'], $sut->getPermissions());
        self::assertEquals(99, $sut->getData([]));
    }
}
