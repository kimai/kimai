<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Saml\SamlTokenFactory;
use App\Saml\User\SamlUserFactory;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class SamlProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var SamlUserFactory
     */
    private $userFactory;
    /**
     * @var SamlTokenFactory
     */
    private $tokenFactory;
    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(UserRepository $repository, UserProviderInterface $userProvider, SamlTokenFactory $tokenFactory, SamlUserFactory $userFactory)
    {
        $this->repository = $repository;
        $this->userProvider = $userProvider;
        $this->tokenFactory = $tokenFactory;
        $this->userFactory = $userFactory;
    }

    /**
     * @param SamlTokenInterface $token
     * @return SamlTokenInterface
     */
    public function authenticate(TokenInterface $token)
    {
        $user = null;

        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
        }

        try {
            if (null === $user) {
                $user = $this->userFactory->createUser($token);
            } else {
                $this->userFactory->hydrateUser($user, $token);
            }

            $this->repository->saveUser($user);
        } catch (\Exception $ex) {
            throw new AuthenticationException(
                sprintf('Failed creating or hydrating user "%s": %s', $token->getUsername(), $ex->getMessage())
            );
        }

        $authenticatedToken = $this->tokenFactory->createToken($user, $token->getAttributes(), $user->getRoles());
        $authenticatedToken->setAuthenticated(true);

        return $authenticatedToken;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SamlTokenInterface;
    }
}
