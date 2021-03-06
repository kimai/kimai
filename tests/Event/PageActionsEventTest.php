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

        $sut = new PageActionsEvent($user, [], 'foo');
        $this->assertSame($user, $sut->getUser());
        $this->assertEquals([], $sut->getActions());
        $this->assertEquals(['actions' => []], $sut->getPayload());

        $sut = new PageActionsEvent($user, ['hello' => 'world'], 'foo');
        $this->assertSame($user, $sut->getUser());
        $this->assertEquals([], $sut->getActions());
        $this->assertEquals(['hello' => 'world', 'actions' => []], $sut->getPayload());
    }

    public function testSetActions()
    {
        $sut = new PageActionsEvent(new User(), ['hello' => 'world'], 'foo');
        $sut->setActions(['foo' => ['url' => 'bar']]);
        $this->assertEquals(['foo' => ['url' => 'bar']], $sut->getActions());
        $this->assertEquals(['hello' => 'world', 'actions' => ['foo' => ['url' => 'bar']]], $sut->getPayload());
    }
}
