<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Configuration\LdapConfiguration;
use App\Entity\User;
use FR3D\LdapBundle\Security\Authentication\LdapAuthenticationProvider as FR3DLdapAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Overwritten to be able to update user after EACH login.
 */
class LdapAuthenticationProvider extends FR3DLdapAuthenticationProvider
{
    /**
     * @var LdapManager
     */
    private $ldapManager;
    /**
     * @var bool
     */
    private $activated = false;

    public function __construct(UserCheckerInterface $userChecker, $providerKey, UserProviderInterface $userProvider, LdapManager $ldapManager, LdapConfiguration $config, $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userChecker, $providerKey, $userProvider, $ldapManager, $hideUserNotFoundExceptions);
        $this->ldapManager = $ldapManager;
        $this->activated = $config->isActivated();
    }

    /**
     * The update should theoretically happen in retrieveUser() but that would require an additional
     * $this->ldapManager->bind($user, $token->getCredentials())
     * in the if clause to check if the user is still valid.
     *
     * Symfony calls retrieveUser() before checkAuthentication()
     * and we should not used ldap->search() before ldap->bind()
     *
     * All changes in here are also reflected into the Doctrine workingSet and the user is properly updated.
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        // do not return early if $user->isLdapUser() returns false,
        // as this would never update a user whose DN was deleted from the database

        if (!$this->activated) {
            // Symfony will check the other configured authentication providers
            // FOSUserBundle will authenticate local users, even if this exception is thrown
            throw new BadCredentialsException('LDAP authentication is deactivated');
        }

        parent::checkAuthentication($user, $token);

        // this statement will only be reached by LDAP users whose bind() succeeded
        if ($user instanceof User) {
            $this->ldapManager->updateUser($user, $user->getUsername());
        }
    }
}
