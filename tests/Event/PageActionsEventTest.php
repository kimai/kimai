<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\PageActionsEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\PageActionsEvent
 */
class PageActionsEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new PageActionsEvent($user, [], 'foo', 'bar');
        self::assertEquals('bar', $sut->getView());
        self::assertEquals('foo', $sut->getActionName());
        self::assertEquals('actions.foo', $sut->getEventName());
        self::assertTrue($sut->isView('bar'));
        self::assertFalse($sut->isView('foo'));
        self::assertFalse($sut->isIndexView());
        self::assertSame($user, $sut->getUser());
        self::assertEquals([], $sut->getActions());
        self::assertEquals(['actions' => [], 'view' => 'bar'], $sut->getPayload());
        self::assertNull($sut->getLocale());

        $sut = new PageActionsEvent($user, ['hello' => 'world'], 'foo', 'bar');
        self::assertSame($user, $sut->getUser());
        self::assertEquals([], $sut->getActions());
        self::assertEquals(['hello' => 'world', 'actions' => [], 'view' => 'bar'], $sut->getPayload());
    }

    public function testSetActions(): void
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');
        $sut->addAction('foo', ['url' => 'bar']);
        self::assertEquals(['foo' => ['url' => 'bar']], $sut->getActions());
        self::assertEquals(['hello' => 'world', 'actions' => ['foo' => ['url' => 'bar']], 'view' => 'xxx'], $sut->getPayload());

        self::assertEquals(1, $sut->countActions());

        // make sure an action with tzhe same name cannot be added
        $sut->addAction('foo', ['url' => 'bar']);
        self::assertEquals(1, $sut->countActions());

        self::assertEquals(0, $sut->countActions('foo'));
        self::assertTrue($sut->hasAction('foo'));
        self::assertFalse($sut->hasAction('sdsd'));

        $sut->removeAction('xxx');
        self::assertEquals(1, $sut->countActions());
        $sut->removeAction('foo');
        self::assertEquals(0, $sut->countActions());

        $sut->addAction('foo', ['url' => 'bar']);
        self::assertEquals(['foo' => ['url' => 'bar']], $sut->getActions());
        $sut->replaceAction('foo', ['url' => 'xyz']);
        self::assertEquals(['foo' => ['url' => 'xyz']], $sut->getActions());

        $sut->setLocale('de');
        self::assertEquals('de', $sut->getLocale());

        $sut->setLocale(null);
        self::assertNull($sut->getLocale());
    }

    public function testSubmenu(): void
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');
        self::assertFalse($sut->hasSubmenu('test'));
        $sut->addActionToSubmenu('test', 'blub', ['url' => 'hello-world']);
        self::assertTrue($sut->hasSubmenu('test'));
        self::assertEquals(['test' => ['children' => ['blub' => ['url' => 'hello-world']]]], $sut->getActions());
        $sut->addActionToSubmenu('test', 'blub1', ['url' => 'hello-world']);
        self::assertEquals(2, $sut->countActions('test'));
    }

    public function testAddHelper(): void
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');

        $sut->addDivider();
        $sut->addColumnToggle('foo2');
        $sut->addDelete('foo3');
        $sut->addCreate('foo5', true);
        $sut->addCreate('foo6', false);
        $sut->addQuickExport('foo7');
        $sut->addEdit('trölölö');

        $expected = [
            'divider0' => null,
            'columns' => ['modal' => '#foo2', 'title' => 'modal.columns.title'],
            'create' => ['url' => 'foo5', 'class' => 'modal-ajax-form', 'title' => 'create', 'accesskey' => 'a'],
            'download' => ['url' => 'foo7', 'class' => 'toolbar-action', 'title' => 'export'],
            'edit' => ['url' => 'trölölö', 'class' => 'modal-ajax-form', 'title' => 'edit'],
            'trash' => ['url' => 'foo3', 'class' => 'modal-ajax-form text-red', 'title' => 'trash'],
        ];
        self::assertEquals(\count($expected), $sut->countActions());

        self::assertEquals($expected, $sut->getActions());
    }

    public function testAddOthers(): void
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');

        // make sure that modal always start with #, no matter what was given
        $sut->addColumnToggle('#fooX');
        self::assertEquals(['columns' => ['modal' => '#fooX', 'title' => 'modal.columns.title']], $sut->getActions());
        // make sure that a second toggle cannot be added
        $sut->addColumnToggle('fooY');
        self::assertEquals(['columns' => ['modal' => '#fooX', 'title' => 'modal.columns.title']], $sut->getActions());

        $sut->removeAction('columns');
        $sut->addColumnToggle('fooY');
        self::assertEquals(['columns' => ['modal' => '#fooY', 'title' => 'modal.columns.title']], $sut->getActions());
    }
}
