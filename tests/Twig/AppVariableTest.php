<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\AppVariable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(AppVariable::class)]
class AppVariableTest extends TestCase
{
    private function getSut(?Request $request = null, ?TokenInterface $token = null): AppVariable
    {
        $stack = new RequestStack();
        if ($request !== null) {
            $stack->push($request);
        }

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return new AppVariable($stack, $tokenStorage);
    }

    public function testGetLocaleReturnsDefaultWhenNoRequest(): void
    {
        self::assertSame('en', $this->getSut()->getLocale());
    }

    public function testGetLocaleReturnsRequestLocale(): void
    {
        $request = new Request();
        $request->setLocale('de');

        self::assertSame('de', $this->getSut($request)->getLocale());
    }

    public function testGetUserReturnsNullWhenNoToken(): void
    {
        self::assertNull($this->getSut()->getUser());
    }

    public function testGetUserReturnsUserFromToken(): void
    {
        $user = $this->createStub(UserInterface::class);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        self::assertSame($user, $this->getSut(null, $token)->getUser());
    }

    public function testGetCurrentRouteReturnsNullWhenNoRequest(): void
    {
        self::assertNull($this->getSut()->getCurrent_route());
    }

    public function testGetCurrentRouteReturnsNullWhenRouteAttributeMissing(): void
    {
        self::assertNull($this->getSut(new Request())->getCurrent_route());
    }

    public function testGetCurrentRouteReturnsNullWhenRouteAttributeIsNotString(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 123);

        self::assertNull($this->getSut($request)->getCurrent_route());
    }

    public function testGetCurrentRouteReturnsRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'homepage');

        self::assertSame('homepage', $this->getSut($request)->getCurrent_route());
    }

    public function testGetFlashesReturnsEmptyArrayWhenNoSession(): void
    {
        self::assertSame([], $this->getSut(new Request())->getFlashes());
    }

    public function testGetFlashesReturnsEmptyArrayWhenRequestStackIsEmpty(): void
    {
        self::assertSame([], $this->getSut()->getFlashes());
    }

    public function testGetFlashesReturnsSessionFlashes(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'ok');
        $session->getFlashBag()->add('error', 'nope');

        $request = new Request();
        $request->setSession($session);

        self::assertSame(
            ['success' => ['ok'], 'error' => ['nope']],
            $this->getSut($request)->getFlashes()
        );
    }

    public function testGetRequestReturnsDefaultsWhenNoRequest(): void
    {
        self::assertSame(
            ['locale' => 'en', 'pathinfo' => null],
            $this->getSut()->getRequest()
        );
    }

    public function testGetRequestReturnsLocaleAndPathInfo(): void
    {
        $request = Request::create('/timesheet');
        $request->setLocale('fr');

        self::assertSame(
            ['locale' => 'fr', 'pathinfo' => '/timesheet'],
            $this->getSut($request)->getRequest()
        );
    }
}
