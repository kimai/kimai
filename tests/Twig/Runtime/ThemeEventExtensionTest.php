<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\User;
use App\Event\ThemeEvent;
use App\Tests\Mocks\Security\CurrentUserFactory;
use App\Twig\Runtime\ThemeEventExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Twig\Runtime\ThemeEventExtension
 */
class ThemeEventExtensionTest extends TestCase
{
    protected function getSut(bool $hasListener = true): ThemeEventExtension
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('hasListeners')->willReturn($hasListener);
        $dispatcher->expects($hasListener ? $this->once() : $this->never())->method('dispatch');

        $user = (new CurrentUserFactory($this))->create(new User());

        return new ThemeEventExtension($dispatcher, $user);
    }

    public function testTrigger()
    {
        $sut = $this->getSut();
        $event = $sut->trigger('foo', []);
        self::assertInstanceOf(ThemeEvent::class, $event);
    }

    public function testTriggerWithoutListener()
    {
        $sut = $this->getSut(false);
        $event = $sut->trigger('foo', []);
        self::assertInstanceOf(ThemeEvent::class, $event);
    }

    public function testJavascriptTranslations()
    {
        $sut = $this->getSut();
        $values = $sut->getJavascriptTranslations();
        self::assertCount(23, $values);
    }
}
