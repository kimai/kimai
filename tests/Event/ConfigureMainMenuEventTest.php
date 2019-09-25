<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\ConfigureMainMenuEvent;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Event\ConfigureMainMenuEvent
 */
class ConfigureMainMenuEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $request = new Request();
        $event = new SidebarMenuEvent();
        $admin = new MenuItemModel('admin', 'admin', 'admin');
        $system = new MenuItemModel('system', 'system', 'system');
        $sut = new ConfigureMainMenuEvent($request, $event, $admin, $system);

        self::assertSame($request, $sut->getRequest());
        self::assertSame($event, $sut->getMenu());
        self::assertSame($admin, $sut->getAdminMenu());
        self::assertSame($system, $sut->getSystemMenu());
    }
}
