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
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapAuthenticationProvider extends UserAuthenticationProvider
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

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
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);

        $this->ldapManager = $ldapManager;
        $this->activated = $config->isActivated();
        $this->userProvider = $userProvider;
    }

    public function supports(TokenInterface $token)
    {
        if (!$this->activated) {
            return false;
        }

        return parent::supports($token);
    }

    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);

            return $user;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            $e = new AuthenticationServiceException($repositoryProblem->getMessage(), (int) $repositoryProblem->getCode(), $repositoryProblem);
            $e->setToken($token);

            throw $e;
        }
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
     *
     * @param UserInterface $user
     * @param UsernamePasswordToken $token
     * @throws LdapDriverException
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        // do not return early if $user->isLdapUser() returns false,
        // as this would never update a user whose DN was deleted from the database

        $currentUser = $token->getUser();
        $presentedPassword = $token->getCredentials();
        if ($currentUser instanceof UserInterface) {
            if ('' === $presentedPassword) {
                throw new BadCredentialsException(
                    'The password in the token is empty. You may forgive turn off `erase_credentials` in your `security.yml`'
                );
            }

            if (!$this->ldapManager->bind($currentUser, $presentedPassword)) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if ('' === $presentedPassword) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if (!$this->ldapManager->bind($user, $presentedPassword)) {
                throw new BadCredentialsException('The presented password is invalid.');
            }
        }

        // this statement will only be reached by LDAP users whose bind() succeeded
        if ($user instanceof User) {
            $this->ldapManager->updateUser($user, $user->getUsername());
        }
    }
}
