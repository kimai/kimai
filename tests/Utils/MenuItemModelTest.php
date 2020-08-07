<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\MenuItemModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\MenuItemModel
 */
class MenuItemModelTest extends TestCase
{
    public function testChildRoutes()
    {
        $sut = new MenuItemModel('test', 'foo', 'bar');

        self::assertFalse($sut->isChildRoute('blub'));
        self::assertFalse($sut->isChildRoute('bla'));

        $sut->addChildRoute('blub');

        self::assertTrue($sut->isChildRoute('blub'));
        self::assertFalse($sut->isChildRoute('bla'));

        $sut->setChildRoutes(['bla']);

        self::assertFalse($sut->isChildRoute('blub'));
        self::assertTrue($sut->isChildRoute('bla'));
    }
}
