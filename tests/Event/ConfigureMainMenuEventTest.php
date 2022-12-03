<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
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
        $menu = new MenuItemModel('main', 'menu.root');
        $admin = new MenuItemModel('admin', 'admin', 'admin');
        $apps = new MenuItemModel('apps', 'apps', 'apps');
        $system = new MenuItemModel('system', 'system', 'system');
        $sut = new ConfigureMainMenuEvent($request, $menu, $apps, $admin, $system);

        self::assertSame($request, $sut->getRequest());
        self::assertSame($menu, $sut->getMenu());
        self::assertSame($admin, $sut->getAdminMenu());
        self::assertSame($apps, $sut->getAppsMenu());
        self::assertSame($system, $sut->getSystemMenu());

        self::assertNull($sut->getTimesheetMenu());
        self::assertNull($sut->getInvoiceMenu());
        self::assertNull($sut->getReportingMenu());

        $timesheet = new MenuItemModel('timesheet', 'timesheet');
        $menu->addChild($timesheet);

        $invoice = new MenuItemModel('invoice', 'invoice');
        $menu->addChild($invoice);

        $reporting = new MenuItemModel('reporting', 'reporting');
        $menu->addChild($reporting);

        self::assertSame($timesheet, $sut->getTimesheetMenu());
        self::assertSame($invoice, $sut->getInvoiceMenu());
        self::assertSame($reporting, $sut->getReportingMenu());
    }
}
