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
 * @final
 */
class LdapManager
{
    public function __construct(private LdapDriver $driver, private LdapConfiguration $config, private RoleService $roles)
    {
    }

    /**
     * Only executed for unknown local users.
     *
     * @param string $username
     * @return User|null
     * @throws \Exception
     */
    public function findUserByUsername(string $username): ?UserInterface
    {
        $params = $this->config->getUserParameters();

        $criteria = [$params['usernameAttribute'] => $username];

        $params = $this->config->getUserParameters();
        $filter = $this->buildFilter($criteria);
        $entries = $this->driver->search($params['baseDn'], $filter);

        if ($entries['count'] > 1) {
            throw new LdapDriverException('This search must only return a single user');
        }

        if (0 === $entries['count']) {
            return null;
        }

        // do not updateUser() here, as this would happen before bind()
        return $this->hydrate($entries[0]);
    }

    private function buildFilter(array $criteria, string $condition = '&'): string
    {
        $params = $this->config->getUserParameters();

        $filters = [];
        $filters[] = $params['filter'];
        foreach ($criteria as $key => $value) {
            $value = ldap_escape($value, '', LDAP_ESCAPE_FILTER);
            $filters[] = sprintf('(%s=%s)', $key, $value);
        }

        return sprintf('(%s%s)', $condition, implode($filters));
    }

    public function bind(string $dn, string $password): bool
    {
        return $this->driver->bind($dn, $password);
    }

    /**
     * This method does all the heavy lifting:
     * - searching for latest 'dn'
     * - syncing user attributes
     * - syncing roles
     *
     * @param User $user
     * @throws LdapDriverException
     */
    public function updateUser(User $user)
    {
        $baseDn = $user->getPreferenceValue('ldap.dn');

        if (null === $baseDn) {
            throw new LdapDriverException('This account is not a registered LDAP user');
        }

        // always look up the users current DN first, as the cached DN might have been renamed in LDAP
        $userFresh = $this->findUserByUsername($user->getUserIdentifier());
        if (null === $userFresh || null === ($baseDn = $userFresh->getPreferenceValue('ldap.dn'))) {
            throw new LdapDriverException(sprintf('Failed fetching user DN for %s', $user->getUserIdentifier()));
        }
        $user->setPreferenceValue('ldap.dn', $baseDn);

        $params = $this->config->getUserParameters();
        $entries = $this->driver->search($baseDn, $params['attributesFilter']);

        if ($entries['count'] > 1) {
            throw new LdapDriverException('This search must only return a single user');
        }

        if (0 === $entries['count']) {
            return;
        }

        $this->hydrateUser($user, $entries[0]);

        $roleParameter = $this->config->getRoleParameters();
        if (null === $roleParameter['baseDn']) {
            return;
        }

        $param = $roleParameter['usernameAttribute'];
        if (!isset($entries[0][$param]) && $param !== 'dn') {
            $param = 'dn';
        }

        $roleValue = $entries[0][$param];
        if (\is_array($roleValue)) {
            $roleValue = $roleValue[0];
        }
        $roles = $this->getRoles($roleValue, $roleParameter);

        if (!empty($roles)) {
            $this->hydrateRoles($user, $roles);
        }
    }

    private function getRoles(string $dn, array $roleParameter): array
    {
        $filter = $roleParameter['filter'] ?? '';

        return $this->driver->search(
            $roleParameter['baseDn'],
            sprintf('(&%s(%s=%s))', $filter, $roleParameter['userDnAttribute'], ldap_escape($dn, '', LDAP_ESCAPE_FILTER)),
            [$roleParameter['nameAttribute']]
        );
    }

    // ===================================================================

    private function createUser(): User
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
        $attributeMap = [];
        if (\array_key_exists('attributes', $userParams)) {
            $attributeMap = $userParams['attributes'];
        }
        $attributeMap = array_merge(
            [
                ['ldap_attr' => $userParams['usernameAttribute'], 'user_method' => 'setUserIdentifier'],
            ],
            $attributeMap
        );

        $this->hydrateUserWithAttributesMap($user, $ldapEntry, $attributeMap);

        /** @var string|array|null $email */
        $email = $user->getEmail();
        if (null === $email) {
            $user->setEmail($user->getUserIdentifier());
        }

        // fill them after hydrating account, so they can't be overwritten
        // by the mapping attributes
        if ($user->getId() === null) {
            $user->setPassword('');
        }
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
        $groupNameMapping = [];
        if (\array_key_exists('groups', $roleParams)) {
            $groupNameMapping = $roleParams['groups'];
        }
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

            if (!\in_array($roleName, $allowedRoles, true)) {
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

    private function hydrateUserWithAttributesMap(UserInterface $user, array $ldapUserAttributes, array $attributeMap)
    {
        $sawUsername = false;
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

            // BC layer for 2.0
            if ($attr['user_method'] === 'setUsername') {
                @trigger_error('Your LDAP configuration is deprecated: change the attribute mapping from "setUsername" to "setUserIdentifier".', E_USER_DEPRECATED);
                $attr['user_method'] = 'setUserIdentifier';
            }

            if ($attr['user_method'] === 'setEmail') {
                if (\is_array($value)) {
                    $value = $value[0];
                }
            } elseif ($attr['user_method'] === 'setUserIdentifier') {
                $sawUsername = true;
            }

            if (!method_exists($user, $attr['user_method'])) {
                throw new \Exception('Unknown mapping method: ' . $attr['user_method']);
            }

            $user->{$attr['user_method']}($value);
        }

        if (!$sawUsername) {
            throw new LdapDriverException('Missing username in LDAP hydration');
        }
    }
}
