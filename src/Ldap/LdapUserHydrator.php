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
use App\Security\RoleService;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
class LdapUserHydrator
{
    /**
     * @var LdapConfiguration
     */
    private $config;
    /**
     * @var RoleService
     */
    private $roles;

    public function __construct(LdapConfiguration $config, RoleService $roles)
    {
        $this->config = $config;
        $this->roles = $roles;
    }

    protected function createUser(): User
    {
        $user = new User();
        $user->setEnabled(true);

        return $user;
    }

    public function hydrate(array $ldapEntry): User
    {
        $user = $this->createUser();
        $this->hydrateUser($user, $ldapEntry);

        return $user;
    }

    public function hydrateUser(User $user, array $ldapEntry)
    {
        $userParams = $this->config->getUserParameters();
        $attributeMap = $userParams['attributes'];
        $attributeMap = array_merge(
            [
                ['ldap_attr' => $userParams['usernameAttribute'], 'user_method' => 'setUsername'],
            ],
            $attributeMap
        );

        $this->hydrateUserWithAttributesMap($user, $ldapEntry, $attributeMap);

        /** @var string|array|null $email */
        $email = $user->getEmail();

        if (\is_array($email)) {
            $user->setEmail($email[0]);
        }

        if (null === $email) {
            $user->setEmail($user->getUsername());
        }

        // fill them after hydrating account, so they can't be overwritten
        $user->setPassword('');
        $user->setAuth(User::AUTH_LDAP);

        $user->setPreferenceValue('ldap.dn', $ldapEntry['dn']);
    }

    /**
     * @param User $user
     * @param array $entries
     */
    public function hydrateRoles(User $user, array $entries)
    {
        $roleParams = $this->config->getRoleParameters();
        $allowedRoles = $this->roles->getAvailableNames();
        $groupNameMapping = $roleParams['groups'];
        $roleNameAttr = $roleParams['nameAttribute'];

        $roles = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $roleName = $entries[$i][$roleNameAttr][0];
            $mapped = false;
            foreach ($groupNameMapping as $attr) {
                if ($roleName === $attr['ldap_value']) {
                    $roleName = $attr['role'];
                    $mapped = true;
                }
            }

            if (!$mapped) {
                $roleName = sprintf('ROLE_%s', self::slugify($roleName));
            }

            if (!\in_array($roleName, $allowedRoles)) {
                continue;
            }

            $roles[] = $roleName;
        }

        $user->setRoles($roles);
    }

    private static function slugify(string $role): string
    {
        $role = preg_replace('/\W+/', '_', $role);
        $role = trim($role, '_');
        $role = strtoupper($role);

        return $role;
    }

    protected function hydrateUserWithAttributesMap(UserInterface $user, array $ldapUserAttributes, array $attributeMap)
    {
        /** @var array $attr */
        foreach ($attributeMap as $attr) {
            if (!\array_key_exists($attr['ldap_attr'], $ldapUserAttributes)) {
                continue;
            }

            $ldapValue = $ldapUserAttributes[$attr['ldap_attr']];

            if (\array_key_exists('count', $ldapValue)) {
                unset($ldapValue['count']);
            }

            if (1 === \count($ldapValue)) {
                $value = array_shift($ldapValue);
            } else {
                $value = $ldapValue;
            }

            $user->{$attr['user_method']}($value);
        }
    }
}
