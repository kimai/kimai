<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml;

use App\Configuration\SamlConfigurationInterface;
use App\Entity\User;
use App\User\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class SamlProvider
{
    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly UserService $userService,
        private readonly UserProviderInterface $userProvider,
        private readonly SamlConfigurationInterface $configuration,
        private readonly LoggerInterface $logger
    ) {
    }

    public function findUser(SamlLoginAttributes $token): User
    {
        $user = null;

        try {
            if ($token->getUserIdentifier() !== null) {
                /** @var User $user */
                $user = $this->userProvider->loadUserByIdentifier($token->getUserIdentifier());
            }
        } catch (UserNotFoundException $ex) {
            // this is expected for new users
            $this->logger->debug('User is not existing: ' . $token->getUserIdentifier());
        }

        try {
            if (null === $user) {
                $user = $this->userService->createNewUser();
                $user->setUserIdentifier($token->getUserIdentifier());
            }
            $this->hydrateUser($user, $token);
            $this->userService->saveUser($user);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            throw new AuthenticationException(
                sprintf('Failed creating or hydrating user "%s": %s', $token->getUserIdentifier(), $ex->getMessage())
            );
        }

        return $user;
    }

    private function hydrateUser(User $user, SamlLoginAttributes $token): void
    {
        $groupAttribute = $this->configuration->getRolesAttribute();
        $groupMapping = $this->configuration->getRolesMapping();

        // extract user roles from a special saml attribute
        if (!empty($groupAttribute) && $token->hasAttribute($groupAttribute)) {
            $groupMap = [];
            foreach ($groupMapping as $mapping) {
                $field = $mapping['kimai'];
                $attribute = $mapping['saml'];
                $groupMap[$attribute] = $field;
            }

            $roles = [];
            $samlGroups = $token->getAttribute($groupAttribute);
            foreach ($samlGroups as $groupName) {
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

        $mappingConfig = $this->configuration->getAttributeMapping();

        foreach ($mappingConfig as $mapping) {
            $field = $mapping['kimai'];
            $attribute = $mapping['saml'];
            $value = $this->getPropertyValue($token, $attribute);
            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            } else {
                // this should never happen, because it is validated when the container is built
                throw new \RuntimeException('Invalid SAML mapping field: ' . $field);
            }
        }

        // change after hydrating account, so it can't be overwritten by mapping attributes
        if ($user->getId() === null) {
            // set a plain password to satisfy the validator
            $user->setPlainPassword(substr(bin2hex(random_bytes(100)), 0, 50));
            $user->setPassword('');
        }

        $user->setUserIdentifier($token->getUserIdentifier());
        $user->setAuth(User::AUTH_SAML);
    }

    private function getPropertyValue(SamlLoginAttributes $token, $attribute): string
    {
        $results = [];
        $attributes = $token->getAttributes();

        $parts = explode(' ', $attribute);
        foreach ($parts as $part) {
            if (empty(trim($part))) {
                continue;
            }
            if ($part[0] === '$') {
                $key = substr($part, 1);
                if (!\array_key_exists($key, $attributes)) {
                    throw new \RuntimeException('Missing SAML attribute in response: ' . $key);
                }

                if (\is_array($attributes[$key]) && isset($attributes[$key][0])) {
                    $results[] = $attributes[$key][0];
                }
            } else {
                $results[] = $part;
            }
        }

        if (!empty($results)) {
            return implode(' ', $results);
        }

        return $attribute;
    }
}
