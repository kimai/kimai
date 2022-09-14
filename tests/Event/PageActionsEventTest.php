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
    public function testDefaultValues()
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new PageActionsEvent($user, [], 'foo', 'bar');
        $this->assertEquals('bar', $sut->getView());
        $this->assertEquals('foo', $sut->getActionName());
        $this->assertTrue($sut->isView('bar'));
        $this->assertFalse($sut->isView('foo'));
        $this->assertFalse($sut->isIndexView());
        $this->assertSame($user, $sut->getUser());
        $this->assertEquals([], $sut->getActions());
        $this->assertEquals(['actions' => [], 'view' => 'bar'], $sut->getPayload());

        $sut = new PageActionsEvent($user, ['hello' => 'world'], 'foo', 'bar');
        $this->assertSame($user, $sut->getUser());
        $this->assertEquals([], $sut->getActions());
        $this->assertEquals(['hello' => 'world', 'actions' => [], 'view' => 'bar'], $sut->getPayload());
    }

    public function testSetActions()
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');
        $sut->addAction('foo', ['url' => 'bar']);
        $this->assertEquals(['foo' => ['url' => 'bar']], $sut->getActions());
        $this->assertEquals(['hello' => 'world', 'actions' => ['foo' => ['url' => 'bar']], 'view' => 'xxx'], $sut->getPayload());

        $this->assertEquals(1, $sut->countActions());

        // make sure an action with tzhe same name cannot be added
        $sut->addAction('foo', ['url' => 'bar']);
        $this->assertEquals(1, $sut->countActions());

        $this->assertEquals(0, $sut->countActions('foo'));
        $this->assertTrue($sut->hasAction('foo'));
        $this->assertFalse($sut->hasAction('sdsd'));

        $sut->removeAction('xxx');
        $this->assertEquals(1, $sut->countActions());
        $sut->removeAction('foo');
        $this->assertEquals(0, $sut->countActions());

        $sut->addAction('foo', ['url' => 'bar']);
        $this->assertEquals(['foo' => ['url' => 'bar']], $sut->getActions());
        $sut->replaceAction('foo', ['url' => 'xyz']);
        $this->assertEquals(['foo' => ['url' => 'xyz']], $sut->getActions());
    }

    public function testSubmenu()
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');
        $this->assertFalse($sut->hasSubmenu('test'));
        $sut->addActionToSubmenu('test', 'blub', ['url' => 'hello-world']);
        $this->assertTrue($sut->hasSubmenu('test'));
        $this->assertEquals(['test' => ['children' => ['blub' => ['url' => 'hello-world']]]], $sut->getActions());
        $sut->addActionToSubmenu('test', 'blub1', ['url' => 'hello-world']);
        $this->assertEquals(2, $sut->countActions('test'));
    }

    public function testAddHelper()
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');

        $sut->addSearchToggle();
        $sut->addDivider();
        $sut->addBack('foo1');
        $sut->addColumnToggle('foo2');
        $sut->addDelete('foo3');
        $sut->addHelp('foo4');
        $sut->addCreate('foo5', true);
        $sut->addCreate('foo6', false);
        $sut->addQuickExport('foo7');

        $this->assertEquals(8, $sut->countActions());

        $expected = [
            'search' => ['modal' => '#modal_search', 'label' => null, 'accesskey' => 'q'],
            'divider0' => null,
            'back' => ['url' => 'foo1', 'translation_domain' => 'actions'],
            'visibility' => ['modal' => '#foo2'],
            'help' => ['url' => 'foo4', 'target' => '_blank', 'accesskey' => 'h'],
            'create' => ['url' => 'foo5', 'class' => 'modal-ajax-form', 'accesskey' => 'a'],
            'download' => ['url' => 'foo7', 'class' => 'toolbar-action'],
            'trash' => ['url' => 'foo3', 'class' => 'modal-ajax-form text-red'],
        ];

        $this->assertEquals($expected, $sut->getActions());
    }

    public function testAddOthers()
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo', 'xxx');

        // make sure that modal always start with #, no matter what was given
        $sut->addColumnToggle('#fooX');
        $this->assertEquals(['visibility' => ['modal' => '#fooX']], $sut->getActions());
        // make sure that a second toggle cannot be added
        $sut->addColumnToggle('fooY');
        $this->assertEquals(['visibility' => ['modal' => '#fooX']], $sut->getActions());

        $sut->removeAction('visibility');
        $sut->addColumnToggle('fooY');
        $this->assertEquals(['visibility' => ['modal' => '#fooY']], $sut->getActions());
    }
}
