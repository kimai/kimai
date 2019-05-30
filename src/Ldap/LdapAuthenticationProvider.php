<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Entity\User;
use FR3D\LdapBundle\Ldap\LdapManagerInterface;
use FR3D\LdapBundle\Security\Authentication\LdapAuthenticationProvider as FR3DLdapAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapAuthenticationProvider extends FR3DLdapAuthenticationProvider
{
    /**
     * @var LdapManagerInterface
     */
    private $ldapManager;

    public function __construct(UserCheckerInterface $userChecker, $providerKey, UserProviderInterface $userProvider, LdapManagerInterface $ldapManager, $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userChecker, $providerKey, $userProvider, $ldapManager, $hideUserNotFoundExceptions);
        $this->ldapManager = $ldapManager;
    }

    /**
     * @param string $username
     * @param UsernamePasswordToken $token
     * @return object|string|\Symfony\Component\Security\Core\User\UserInterface
     * @throws \FR3D\LdapBundle\Driver\LdapDriverException
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = parent::retrieveUser($username, $token);

        // if this is an LDAP user with an assigned DN, go and update all attributes from LDAP
        if ($user instanceof User && $user->isLdapUser()) {
            if ($this->ldapManager->bind($user, $token->getCredentials())) {
                if (method_exists($this->ldapManager, 'updateUser')) {
                    $this->ldapManager->updateUser($user, $username);
                }
            }
        }

        return $user;
    }
}
