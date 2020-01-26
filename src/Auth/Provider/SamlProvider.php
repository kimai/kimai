<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth\Provider;

use App\Auth\User\SamlUserFactory;
use Doctrine\ORM\EntityManager;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
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
     * @var SamlTokenFactoryInterface
     */
    private $tokenFactory;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var array
     */
    private $options;

    public function __construct(UserProviderInterface $userProvider, array $options = [])
    {
        $this->userProvider = $userProvider;
        $this->options = $options;
    }

    // the following setters are here, becuase the bundle creates the service like this and we (unfortunately)
    // cannot simply change that to constructor injection

    public function setUserFactory(SamlUserFactory $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    public function setTokenFactory(SamlTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = null;

        try {
            $user = $this->userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
        }

        try {
            if (null === $user) {
                $user = $this->userFactory->createUser($token);
            } else {
                $this->userFactory->hydrateUser($user, $token);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $ex) {
            throw new AuthenticationException('Failed creating or hydrating user: ' . $ex->getMessage());
        }

        if ($user) {
            $authenticatedToken = $this->tokenFactory->createToken($user, $token->getAttributes(), $user->getRoles());
            $authenticatedToken->setAuthenticated(true);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The authentication failed.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SamlTokenInterface;
    }
}
