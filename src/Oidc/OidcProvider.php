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

        try {
            if ($token->getUserIdentifier() !== null) {
                /** @var User $user */
                $user = $this->userProvider->loadUserByIdentifier($token->getUserIdentifier());
            }
        } catch (UserNotFoundException) {
            // this is expected for new users
            $this->logger->debug('OIDC user is not existing: ' . $token->getUserIdentifier());
        }

        try {
            if (null === $user) {
                $user = $this->userService->createNewUser();
                $user->setUserIdentifier((string) $token->getUserIdentifier());
            }
            $this->hydrateUser($user, $token);
            $this->userService->saveUser($user);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            throw new AuthenticationException(
                \sprintf('Failed creating or hydrating user "%s": %s', $token->getUserIdentifier() ?? '*unknown*', $ex->getMessage())
            );
        }

        return $user;
    }

    private function hydrateUser(User $user, OidcLoginAttributes $token): void
    {
        $rolesClaim = $this->configuration->getRolesClaim();
        $rolesMapping = $this->configuration->getRolesMapping();

        // extract user roles from a dedicated claim (e.g. "groups")
        if (!empty($rolesClaim) && $token->hasAttribute($rolesClaim)) {
            $roleMap = [];
            foreach ($rolesMapping as $mapping) {
                $roleMap[$mapping['oidc']] = $mapping['kimai'];
            }

            $roles = [];
            $claimGroups = $token->getAttribute($rolesClaim);
            if (!\is_array($claimGroups)) {
                $claimGroups = [$claimGroups];
            }
            foreach ($claimGroups as $groupName) {
                if (\is_string($groupName) && \array_key_exists($groupName, $roleMap)) {
                    $roles[] = $roleMap[$groupName];
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

        foreach ($this->configuration->getAttributeMapping() as $mapping) {
            $field = $mapping['kimai'];
            $value = $this->getClaimValue($token, $mapping['oidc']);
            if ($value === null) {
                continue;
            }
            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            } else {
                throw new \RuntimeException('Invalid OIDC mapping field: ' . $field);
            }
        }

        // change after hydrating account, so it can't be overwritten by mapping attributes
        if ($user->getId() === null) {
            // set a plain password to satisfy the validator
            $user->setPlainPassword(substr(bin2hex(random_bytes(100)), 0, 50));
            $user->setPassword('');
        }

        $user->setUserIdentifier((string) $token->getUserIdentifier());
        $user->setAuth(User::AUTH_OIDC);
    }

    private function getClaimValue(OidcLoginAttributes $token, string $claim): ?string
    {
        if (!$token->hasAttribute($claim)) {
            return null;
        }

        $value = $token->getAttribute($claim);

        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!\is_scalar($value) || \is_bool($value)) {
            return null;
        }

        return (string) $value;
    }
}
