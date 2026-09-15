<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use App\Configuration\OidcConfigurationInterface;
use App\Entity\User;
use App\User\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class OidcProvider
{
    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly UserService $userService,
        private readonly UserProviderInterface $userProvider,
        private readonly OidcConfigurationInterface $configuration,
        private readonly LoggerInterface $logger
    ) {
    }

    public function findUser(OidcLoginAttributes $token): User
    {
        $user = null;
        $userId = $token->getUserIdentifier();

        if ($userId === null) {
            throw new AuthenticationException('Unable to find user with no identifier');
        }

        try {
            $user = $this->userProvider->loadUserByIdentifier($userId);
        } catch (UserNotFoundException $ex) {
            // this is expected for new users
            $this->logger->debug('User is not existing: ' . $userId);
        }

        try {
            if (null === $user) {
                $user = $this->userService->createNewUser();
                $user->setUserIdentifier($userId);
            }
            $this->hydrateUser($user, $token);
            $this->userService->saveUser($user);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            throw new AuthenticationException(
                \sprintf('Failed creating or hydrating user "%s": %s', $userId, $ex->getMessage())
            );
        }

        return $user;
    }

    private function hydrateUser(User $user, OidcLoginAttributes $token): void
    {
        // extract user roles from the oidc "groups" attribute
        $groupMapping = $this->configuration->getRolesMapping();
        if ($token->hasAttribute('groups')) {
            $groupMap = [];
            foreach ($groupMapping as $mapping) {
                $field = $mapping['kimai'];
                $attribute = $mapping['oidc'];
                $groupMap[$attribute] = $field;
            }

            $roles = [];
            $oidcGroups = $token->getAttribute('groups');
            foreach ($oidcGroups as $groupName) {
                if (\array_key_exists($groupName, $groupMap)) {
                    $roles[] = $groupMap[$groupName];
                }
            }
            if ($this->configuration->isRolesResetOnLogin()) {
                $user->setRoles($roles);
            } else {
                foreach ($roles as $role) {
                    $user->addRole($role);
                }
            }
        }

        // map the user attributes onto the user
        if ($token->hasAttribute('display_name')) {
            $user->setAlias($token->getAttribute('display_name'));
        }
        if ($token->hasAttribute('email')) {
            $user->setEmail($token->getAttribute('email'));
        }
        if ($token->hasAttribute('picture')) {
            $user->setAvatar($token->getAttribute('picture'));
        }

        // If the user is new, set a plain password to satisfy the validator
        if ($user->getId() === null) {
            $user->setPlainPassword(substr(bin2hex(random_bytes(100)), 0, 50));
            $user->setPassword('');
        }

        $user->setUserIdentifier($token->getUserIdentifier());
        $user->setAuth(User::AUTH_OIDC);
    }
}
