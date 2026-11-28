<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Ldap\LdapBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Prevents that LDAP and SAML accounts log in with the local password via the form login.
 *
 * The local password of external accounts is invalidated when the authentication changes
 * (see User::setAuth()), this check additionally protects accounts which were migrated
 * before and still own their old password hash.
 */
final class ExternalUserPasswordLoginSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // runs after the LdapCredentialsSubscriber and the CheckCredentialsListener (both use priority 0)
        return [CheckPassportEvent::class => ['onCheckPassport', -10]];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        $user = $passport->getUser();
        if (!($user instanceof User) || $user->isInternalUser()) {
            return;
        }

        // LDAP accounts may use the form login, if the password was verified by the LDAP server
        // the LdapCredentialsSubscriber throws an exception if the bind() fails for LDAP accounts
        if ($user->isLdapUser() && $passport->hasBadge(LdapBadge::class)) {
            /** @var LdapBadge $ldapBadge */
            $ldapBadge = $passport->getBadge(LdapBadge::class);
            if ($ldapBadge->isResolved()) {
                return;
            }
        }

        throw new BadCredentialsException('The presented password is invalid.');
    }
}
