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

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
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
            // this will always query the FOSUserBundle first...
            // only first-time logins from LDAP user (not yet existing in local user database)
            // will actually hit the LdapUserProvider
            $user = $this->userProvider->loadUserByUsername($username);

            // do not update the user here from LDAP, as we don't know if the user can be authenticated

        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            $e = new AuthenticationServiceException($repositoryProblem->getMessage(), (int) $repositoryProblem->getCode(), $repositoryProblem);
            $e->setToken($token);

            throw $e;
        }

        return $user;
    }

    /**
     * The updateUser() call should theoretically happen in retrieveUser() but that would require an additional
     * $this->ldapManager->bind($user, $token->getCredentials())
     * to check if the user is still valid.
     *
     * Symfony calls retrieveUser() before checkAuthentication()
     * and we should not used ldap->search() before ldap->bind()
     *
     * @param UserInterface $user
     * @param UsernamePasswordToken $token
     * @throws LdapDriverException
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $currentUser = $token->getUser();
        $presentedPassword = $token->getCredentials();
        if ($currentUser instanceof UserInterface) {
            if ('' === $presentedPassword) {
                throw new BadCredentialsException(
                    'The password in the token is empty. Check `erase_credentials` in your `security.yaml`'
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

        if ($user instanceof User && null !== $user->getPreferenceValue('ldap.dn')) {
            $this->ldapManager->updateUser($user);
        }
    }
}
