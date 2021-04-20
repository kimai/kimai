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

    public function getProgressbarColors()
    {
        yield ['progress-bar-danger', 100, false];
        yield ['progress-bar-danger', 91, false];
        yield ['progress-bar-warning', 90, false];
        yield ['progress-bar-warning', 80, false];
        yield ['progress-bar-warning', 71, false];
        yield ['progress-bar-success', 70, false];
        yield ['progress-bar-success', 60, false];
        yield ['progress-bar-success', 51, false];
        yield ['progress-bar-primary', 50, false];
        yield ['progress-bar-primary', 40, false];
        yield ['progress-bar-primary', 31, false];
        yield ['progress-bar-info', 30, false];
        yield ['progress-bar-info', 20, false];
        yield ['progress-bar-info', 10, false];
        yield ['progress-bar-info', 0, false];
        yield ['progress-bar-primary', 100, true];
        yield ['progress-bar-primary', 91, true];
        yield ['progress-bar-success', 90, true];
        yield ['progress-bar-success', 80, true];
        yield ['progress-bar-success', 71, true];
        yield ['progress-bar-warning', 70, true];
        yield ['progress-bar-warning', 60, true];
        yield ['progress-bar-warning', 51, true];
        yield ['progress-bar-danger', 50, true];
        yield ['progress-bar-danger', 40, true];
        yield ['progress-bar-danger', 31, true];
        yield ['progress-bar-info', 30, true];
        yield ['progress-bar-info', 20, true];
        yield ['progress-bar-info', 10, true];
        yield ['progress-bar-info', 0, true];
    }

    /**
     * @dataProvider getProgressbarColors
     */
    public function testProgressbarClass(string $expected, int $percent, ?bool $reverseColors = false)
    {
        $sut = $this->getSut(false);
        self::assertEquals($expected, $sut->getProgressbarClass($percent, $reverseColors));
    }
}
