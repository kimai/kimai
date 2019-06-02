<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\ConfigureAdminMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Event\ConfigureAdminMenuEvent
 */
class ConfigureAdminMenuEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $request = new Request();
        $request->setLocale('de');

        $admin = new MenuItemModel('admin', 'foo', 'bar');
        $sut = new ConfigureAdminMenuEvent($request, $admin);

        $this->assertEquals($request, $sut->getRequest());
        $this->assertEquals($admin, $sut->getAdminMenu());
    }
}
