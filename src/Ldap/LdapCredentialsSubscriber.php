<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Entity\User;
use App\User\UserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @internal
 */
final class LdapCredentialsSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LdapManager $ldapManager, private readonly UserService $userService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['onCheckPassport']];
    }

    public function onCheckPassport(CheckPassportEvent $event)
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(LdapBadge::class)) {
            return;
        }

        /** @var LdapBadge $ldapBadge */
        $ldapBadge = $passport->getBadge(LdapBadge::class);
        if ($ldapBadge->isResolved()) {
            return;
        }

        if (!$passport->hasBadge(PasswordCredentials::class)) {
            throw new \LogicException(\sprintf('LDAP authentication requires a passport containing a user and password credentials, authenticator "%s" does not fulfill these requirements.', \get_class($event->getAuthenticator())));
        }

        /** @var PasswordCredentials $passwordCredentials */
        $passwordCredentials = $passport->getBadge(PasswordCredentials::class);
        if ($passwordCredentials->isResolved()) {
            throw new \LogicException('LDAP authentication password verification cannot be completed because something else has already resolved the PasswordCredentials.');
        }

        $presentedPassword = $passwordCredentials->getPassword();
        if ('' === $presentedPassword) {
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        $user = $passport->getUser();
        $ldapBadge->markResolved();

        if (!($user instanceof User)) {
            throw new BadCredentialsException('The presented user needs to be a Kimai user.');
        }

        if (!$this->ldapManager->bind($user->getUserIdentifier(), $presentedPassword)) {
            // if the login failed and the user is registered with "kimai" auth, simply return:
            // the FormLogin authenticator will take over and the user can log in via internal database
            if (!$user->isLdapUser()) {
                return;
            }
            throw new BadCredentialsException('The presented password is invalid.');
        }

        try {
            $this->ldapManager->updateUser($user);
        } catch (LdapDriverException $ex) {
            throw new BadCredentialsException('Fetching user data/roles failed, probably DN is expired.');
        }

        if ($user->getId() === null) {
            // set a plain password to satisfy the validator, it is never used to authenticate LDAP users
            $user->setPlainPassword(substr(bin2hex(random_bytes(100)), 0, 50));
        }

        // new users only exist in memory at this point and the synced attributes/roles of existing
        // users have to be written as well: nothing else in the login process stores the user
        // (see SamlProvider::findUser() which does the same for SAML logins)
        try {
            $this->userService->saveUser($user);
        } catch (\Exception $ex) {
            throw new AuthenticationException(
                \sprintf('Failed creating or updating user "%s": %s', $user->getUserIdentifier(), $ex->getMessage())
            );
        }

        // make sure that the normal auth process is not triggered
        $passwordCredentials->markResolved(); // @phpstan-ignore method.internal
    }
}
