<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\ExternalUserPasswordLoginSubscriber;
use App\Ldap\LdapBadge;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[CoversClass(ExternalUserPasswordLoginSubscriber::class)]
class ExternalUserPasswordLoginSubscriberTest extends TestCase
{
    private function createUser(string $auth): User
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth($auth);

        return $user;
    }

    private function createEvent(User $user, ?LdapBadge $ldapBadge = null): CheckPassportEvent
    {
        $badges = $ldapBadge !== null ? [$ldapBadge] : [];
        $passport = new Passport(new UserBadge($user->getUserIdentifier(), fn () => $user), new PasswordCredentials('foo-secret'), $badges);

        return new CheckPassportEvent($this->createMock(AuthenticatorInterface::class), $passport);
    }

    private function createResolvedLdapBadge(): LdapBadge
    {
        $badge = new LdapBadge();
        $badge->markResolved();

        return $badge;
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ExternalUserPasswordLoginSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(CheckPassportEvent::class, $events);
        $config = $events[CheckPassportEvent::class];
        // must run after the LdapCredentialsSubscriber and the CheckCredentialsListener (priority 0)
        self::assertSame(['onCheckPassport', -10], $config);
    }

    public function testInternalUserIsAllowed(): void
    {
        $event = $this->createEvent($this->createUser(User::AUTH_INTERNAL));

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($event);

        $this->expectNotToPerformAssertions();
    }

    public function testPassportWithoutPasswordIsIgnored(): void
    {
        $user = $this->createUser(User::AUTH_SAML);
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn () => $user));
        $event = new CheckPassportEvent($this->createMock(AuthenticatorInterface::class), $passport);

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($event);

        $this->expectNotToPerformAssertions();
    }

    public function testLdapUserVerifiedByLdapIsAllowed(): void
    {
        $event = $this->createEvent($this->createUser(User::AUTH_LDAP), $this->createResolvedLdapBadge());

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($event);

        $this->expectNotToPerformAssertions();
    }

    public function testSamlUserIsRejected(): void
    {
        $this->expectException(BadCredentialsException::class);

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($this->createEvent($this->createUser(User::AUTH_SAML)));
    }

    public function testSamlUserIsRejectedWithLdapBadge(): void
    {
        // LDAP is activated, but the bind() failed for the SAML user and the form login took over
        $this->expectException(BadCredentialsException::class);

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($this->createEvent($this->createUser(User::AUTH_SAML), $this->createResolvedLdapBadge()));
    }

    public function testLdapUserIsRejectedWithoutLdap(): void
    {
        // LDAP was deactivated: the account must not log in with an old local password
        $this->expectException(BadCredentialsException::class);

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($this->createEvent($this->createUser(User::AUTH_LDAP)));
    }

    public function testLdapUserIsRejectedWithUnresolvedLdapBadge(): void
    {
        $this->expectException(BadCredentialsException::class);

        $sut = new ExternalUserPasswordLoginSubscriber();
        $sut->onCheckPassport($this->createEvent($this->createUser(User::AUTH_LDAP), new LdapBadge()));
    }
}
