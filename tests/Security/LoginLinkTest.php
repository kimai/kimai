<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Tests\KernelTestTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\LoginLink\Exception\InvalidLoginLinkException;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * Regression test for GHSA-m492-gv72-xvxj: a login link (used for password
 * reset and admin on-demand login) must stop working once the user's password
 * has been changed.
 */
#[Group('integration')]
class LoginLinkTest extends KernelTestCase
{
    use KernelTestTrait;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        // the login link handler is firewall-aware and needs an active request
        // on the stack to resolve the firewall it belongs to
        $this->request = Request::create('http://localhost/');
        $stack = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);
        $stack->push($this->request);
    }

    private function getLoginLinkHandler(): LoginLinkHandlerInterface
    {
        /** @var LoginLinkHandlerInterface $handler */
        $handler = self::getContainer()->get(LoginLinkHandlerInterface::class);

        return $handler;
    }

    public function testLoginLinkIsValidBeforePasswordChange(): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $handler = $this->getLoginLinkHandler();

        $link = $handler->createLoginLink($user, $this->request);

        $consumed = $handler->consumeLoginLink(Request::create($link->getUrl()));

        self::assertSame($user->getUserIdentifier(), $consumed->getUserIdentifier());
    }

    public function testLoginLinkIsRejectedAfterPasswordChange(): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $handler = $this->getLoginLinkHandler();

        $link = $handler->createLoginLink($user, $this->request);

        // simulate the user completing the password reset wizard: the password
        // hash changes, which must invalidate the signature of the old link
        $user->setPassword('$2y$13$changedchangedchangedchangedchangedchangedchangedchangedchg');
        $this->getEntityManager()->flush();

        $this->expectException(InvalidLoginLinkException::class);
        $handler->consumeLoginLink(Request::create($link->getUrl()));
    }
}
