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
use App\Twig\Runtime\ThemeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Twig\Runtime\ThemeExtension
 */
class ThemeEventExtensionTest extends TestCase
{
    protected function getSut(bool $hasListener = true): ThemeExtension
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('hasListeners')->willReturn($hasListener);
        $dispatcher->expects($hasListener ? $this->once() : $this->never())->method('dispatch');

        $user = (new CurrentUserFactory($this))->create(new User());

        return new ThemeExtension($dispatcher);
    }

    protected function getEnvironment(): Environment
    {
        $mock = $this->getMockBuilder(UsernamePasswordToken::class)->onlyMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $mock->method('getUser')->willReturn(new User());
        /** @var UsernamePasswordToken $token */
        $token = $mock;

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $app = new AppVariable();
        $app->setTokenStorage($tokenStorage);

        $environment = new Environment(new FilesystemLoader());
        $environment->addGlobal('app', $app);

        return $environment;
    }

    public function testTrigger()
    {
        $sut = $this->getSut();
        $event = $sut->trigger($this->getEnvironment(), 'foo', []);
        self::assertInstanceOf(ThemeEvent::class, $event);
    }

    public function testTriggerWithoutListener()
    {
        $sut = $this->getSut(false);
        $event = $sut->trigger($this->getEnvironment(), 'foo', []);
        self::assertInstanceOf(ThemeEvent::class, $event);
    }

    public function testJavascriptTranslations()
    {
        $sut = $this->getSut();
        $values = $sut->getJavascriptTranslations();
        self::assertCount(24, $values);
    }
}
