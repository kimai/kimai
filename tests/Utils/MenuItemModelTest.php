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

        self::assertEquals('foo', $sut->getLabel());
        self::assertEquals('messages', $sut->getTranslationDomain());

        $sut->setTranslationDomain('foo');
        self::assertEquals('foo', $sut->getTranslationDomain());

        self::assertFalse($sut->isChildRoute('blub'));
        self::assertFalse($sut->isChildRoute('bla'));

        $sut->addChildRoute('blub');

        self::assertTrue($sut->isChildRoute('blub'));
        self::assertFalse($sut->isChildRoute('bla'));

        $sut->setChildRoutes(['bla']);

        self::assertFalse($sut->isChildRoute('blub'));
        self::assertTrue($sut->isChildRoute('bla'));

        self::assertNull($sut->getIcon());
        $sut->setIcon('123456789');
        self::assertEquals('123456789', $sut->getIcon());

        self::assertFalse($sut->isDivider());
        $sut->setDivider(true);
        self::assertTrue($sut->isDivider());

        self::assertNull($sut->getBadge());
        $sut->setBadge('22');
        self::assertEquals('22', $sut->getBadge());

        self::assertNull($sut->getBadgeColor());
        $sut->setBadgeColor('red');
        self::assertEquals('red', $sut->getBadgeColor());

        self::assertEquals([], $sut->getRouteArgs());
        $sut->setRouteArgs(['sdf' => 'dfsgdfg', 'xxx' => 2345]);
        self::assertEquals(['sdf' => 'dfsgdfg', 'xxx' => 2345], $sut->getRouteArgs());

        self::assertNull($sut->getParent());
        $parent = new MenuItemModel('a', 'b');
        self::assertEquals('b', $parent->getLabel());
        $parent->setLabel('x');
        self::assertEquals('x', $parent->getLabel());
        $sut->setParent($parent);
        self::assertSame($parent, $sut->getParent());

        self::assertNull($sut->getChild('foo'));
        self::assertNull($sut->getActiveChild());

        $child1 = new MenuItemModel('a1', 'a2');
        $child2 = new MenuItemModel('b1', 'b2');
        self::assertFalse($child2->getIsActive());
        $child2->setIsActive(true);
        self::assertTrue($child2->getIsActive());

        $sut->setChildren([$child1, $child2]);
        self::assertEquals([$child1, $child2], $sut->getChildren());
        self::assertEquals($child1, $sut->getChild('a1'));
        self::assertEquals($child2, $sut->getChild('b1'));
        self::assertEquals($child2, $sut->getActiveChild());
    }
}
