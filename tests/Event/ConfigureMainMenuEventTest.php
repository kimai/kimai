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

/**
 * @covers \App\Event\ConfigureMainMenuEvent
 */
class ConfigureMainMenuEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $sut = new ConfigureMainMenuEvent();

        self::assertNotNull($sut->getMenu());
        self::assertNotNull($sut->getAdminMenu());
        self::assertNotNull($sut->getAppsMenu());
        self::assertNotNull($sut->getSystemMenu());

        self::assertNull($sut->getTimesheetMenu());
        self::assertNull($sut->getInvoiceMenu());
        self::assertNull($sut->getReportingMenu());

        $timesheet = new MenuItemModel('times', 'timesheet');
        $sut->getMenu()->addChild($timesheet);
        self::assertNotNull($sut->getTimesheetMenu());
        self::assertSame($timesheet, $sut->getTimesheetMenu());

        $invoice = new MenuItemModel('invoice', 'invoice');
        $sut->getMenu()->addChild($invoice);
        self::assertNotNull($sut->getInvoiceMenu());
        self::assertSame($invoice, $sut->getInvoiceMenu());

        self::assertNull($sut->findById('reporting'));
        self::assertNull($sut->findById('foo'));
        self::assertNull($sut->findById('bar'));

        $reporting = new MenuItemModel('reporting', 'reporting');
        $sut->getMenu()->addChild($reporting);
        $reporting->addChild(new MenuItemModel('foo', 'foo'));
        $reporting->addChild(new MenuItemModel('bar', 'bar'));
        self::assertNotNull($sut->getReportingMenu());
        self::assertSame($reporting, $sut->getReportingMenu());

        self::assertSame($reporting, $sut->findById('reporting'));
        self::assertNotNull($sut->findById('foo'));
        self::assertNotNull($sut->findById('bar'));
    }
}
