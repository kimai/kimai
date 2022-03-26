<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Provider;

use App\Configuration\SamlConfiguration;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Saml\SamlLoginAttributes;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class SamlProvider
{
    public function __construct(
        private UserRepository $repository,
        private UserProviderInterface $userProvider,
        private SamlConfiguration $configuration
    ) {
    }

    public function findUser(SamlLoginAttributes $token): User
    {
        $user = null;

        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByIdentifier($token->getUserIdentifier());
        } catch (UserNotFoundException $e) {
        }

        try {
            if (null === $user) {
                $user = $this->createUser($token);
            } else {
                $this->hydrateUser($user, $token);
            }

            $this->repository->saveUser($user);
        } catch (\Exception $ex) {
            throw new AuthenticationException(
                sprintf('Failed creating or hydrating user "%s": %s', $token->getUserIdentifier(), $ex->getMessage())
            );
        }

        return $user;
    }

    private function createUser(SamlLoginAttributes $token): User
    {
        // Not using UserService: user settings should be set via SAML attributes
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername($token->getUserIdentifier());
        $user->setPassword('');

        $this->hydrateUser($user, $token);

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
            $user->setRoles($roles);
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
                throw new \RuntimeException('Invalid mapping field given: ' . $field);
            }
        }

        // fill them after hydrating account, so they can't be overwritten
        // by the mapping attributes
        if ($user->getId() === null) {
            $user->setPassword('');
        }
        $user->setUsername($token->getUserIdentifier());
        $user->setAuth(User::AUTH_SAML);
    }

    private function getPropertyValue(SamlLoginAttributes $token, $attribute)
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
                if (!isset($attributes[$key])) {
                    throw new \RuntimeException('Missing user attribute: ' . $key);
                }

                $results[] = $attributes[$key][0];
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
