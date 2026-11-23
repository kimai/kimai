<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\MenuItemModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MenuItemModel::class)]
class MenuItemModelTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new MenuItemModel('test', 'foo', 'bar');

        self::assertEquals('test', $sut->getIdentifier());

        self::assertEquals('foo', $sut->getLabel());
        $sut->setLabel('new-label');
        self::assertEquals('new-label', $sut->getLabel());

        self::assertEquals('messages', $sut->getTranslationDomain());
        $sut->setTranslationDomain('custom');
        self::assertEquals('custom', $sut->getTranslationDomain());

        self::assertNull($sut->getIcon());
        $sut->setIcon('icon-123');
        self::assertEquals('icon-123', $sut->getIcon());

        self::assertFalse($sut->isDivider());
        $sut->setDivider(true);
        self::assertTrue($sut->isDivider());

        self::assertNull($sut->getBadge());
        $sut->setBadge('99');
        self::assertEquals('99', $sut->getBadge());

        self::assertNull($sut->getBadgeColor());
        $sut->setBadgeColor('blue');
        self::assertEquals('blue', $sut->getBadgeColor());

        self::assertEquals([], $sut->getRouteArgs());
        $sut->setRouteArgs(['a' => 'b']);
        self::assertEquals(['a' => 'b'], $sut->getRouteArgs());

        self::assertFalse($sut->getIsActive());
        self::assertFalse($sut->isActive());
        $sut->setIsActive(true);
        self::assertTrue($sut->isActive());
        self::assertTrue($sut->getIsActive());

        self::assertFalse($sut->isChildRoute('route1'));
        $sut->setChildRoutes(['route1']);
        self::assertTrue($sut->isChildRoute('route1'));
        $sut->addChildRoute('route2');
        self::assertTrue($sut->isChildRoute('route1'));
        self::assertTrue($sut->isChildRoute('route2'));

        self::assertFalse($sut->isDisabled());
        $sut->setDisabled(true);
        self::assertTrue($sut->isDisabled());

        self::assertFalse($sut->isExpanded());
        $sut->setExpanded(true);
        self::assertTrue($sut->isExpanded());

        // validate default getter values
        self::assertNull($sut->getActiveChild());
        self::assertNull($sut->getChild('x'));

        self::assertEquals('bar', $sut->getRoute());
        $sut->setRoute('bar2');
        self::assertEquals('bar2', $sut->getRoute());

        self::assertNull($sut->getParent());
        $parent = new MenuItemModel('parent', 'parent-label');
        $sut->setParent($parent);
        self::assertSame($parent, $sut->getParent());

        self::assertEquals([], $sut->getChildren());
        $child = new MenuItemModel('child', 'child-label');
        $sut->setChildren([$child]);
        self::assertEquals([$child], $sut->getChildren());
    }

    public function testChildRoutes(): void
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
