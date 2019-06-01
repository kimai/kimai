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
     * @var string[]
     */
    private $attributeMap;
    /**
     * @var array
     */
    private $roleParams;
    /**
     * @var RoleService
     */
    private $roles;

    public function __construct(LdapConfiguration $config, RoleService $roles)
    {
        $attributeMap = $config->getUserParameters();

        $this->attributeMap = $attributeMap['attributes'];
        $this->roleParams = $config->getRoleParameters();
        $this->roles = $roles;
    }

    protected function createUser(): User
    {
        $user = new User();
        $user->setPassword('');
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
        $this->hydrateUserWithAttributesMap($user, $ldapEntry, $this->attributeMap);

        // just a fallback to prevent Exceptions in case no email is available in LDAP
        if (null === $user->getEmail()) {
            $user->setEmail($user->getUsername());
        }
    }

    /**
     * @param User $user
     * @param array $entries
     */
    public function hydrateRoles(User $user, array $entries)
    {
        $allowedRoles = $this->roles->getAvailableNames();
        $groupNameMapping = $this->roleParams['groups'];
        $roleNameAttr = $this->roleParams['nameAttribute'];

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

            if (!in_array($roleName, $allowedRoles)) {
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
            if (!array_key_exists($attr['ldap_attr'], $ldapUserAttributes)) {
                continue;
            }

            $ldapValue = $ldapUserAttributes[$attr['ldap_attr']];

            if (array_key_exists('count', $ldapValue)) {
                unset($ldapValue['count']);
            }

            if (1 === count($ldapValue)) {
                $value = array_shift($ldapValue);
            } else {
                $value = $ldapValue;
            }

            $user->{$attr['user_method']}($value);
        }
    }
}
