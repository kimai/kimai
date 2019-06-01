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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
class LdapManager
{
    /**
     * @var LdapConfiguration
     */
    protected $config;
    /**
     * @var array
     */
    protected $roles = [];
    /**
     * @var LdapDriver
     */
    protected $driver;
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var LdapUserHydrator
     */
    protected $hydrator;

    public function __construct(LdapDriver $driver, LdapUserHydrator $hydrator, LdapConfiguration $config, array $roles)
    {
        $this->params = $config->getUserParameters();
        $this->roles = $roles;
        $this->config = $config;
        $this->driver = $driver;
        $this->hydrator = $hydrator;
    }

    /**
     * @param string $username
     * @return UserInterface|null
     * @throws \Exception
     */
    public function findUserByUsername(string $username): ?UserInterface
    {
        return $this->findUserBy([$this->params['usernameAttribute'] => $username]);
    }

    /**
     * @param array $criteria
     * @return UserInterface|null
     * @throws LdapDriverException
     */
    public function findUserBy(array $criteria): ?UserInterface
    {
        $filter = $this->buildFilter($criteria);
        $entries = $this->driver->search($this->params['baseDn'], $filter);

        if ($entries['count'] > 1) {
            throw new LdapDriverException('This search must only return a single user');
        }

        if (0 === $entries['count']) {
            return null;
        }
        $user = $this->hydrator->hydrate($entries[0]);

        return $user;
    }

    /**
     * Build Ldap filter
     *
     * @param array $criteria
     * @param string $condition
     * @return string
     */
    protected function buildFilter(array $criteria, string $condition = '&'): string
    {
        $filters = [];
        $filters[] = $this->params['filter'];
        foreach ($criteria as $key => $value) {
            $value = ldap_escape($value, '', LDAP_ESCAPE_FILTER);
            $filters[] = sprintf('(%s=%s)', $key, $value);
        }

        return sprintf('(%s%s)', $condition, implode($filters));
    }

    public function bind(UserInterface $user, string $password): bool
    {
        return $this->driver->bind($user, $password);
    }

    /**
     * @param User $user
     * @param string $username
     * @throws LdapDriverException
     */
    public function updateUser(User $user, $username)
    {
        $filter = $this->buildFilter([$this->params['usernameAttribute'] => $username]);
        $entries = $this->driver->search($this->params['baseDn'], $filter);

        if ($entries['count'] > 1) {
            throw new LdapDriverException('This search must only return a single user');
        }

        if (0 === $entries['count']) {
            return;
        }

        if ($this->hydrator instanceof LdapUserHydrator) {
            $this->hydrator->hydrateUser($user, $entries[0]);
            $this->addRoles($user, $entries[0]);
        }
    }

    /**
     * @param User $user
     * @param array $entry
     * @throws LdapDriverException
     */
    private function addRoles(User $user, $entry)
    {
        $roleParameter = $this->config->getRoleParameters();

        if (null === $roleParameter['baseDn']) {
            return;
        }

        $groupNameMapping = $roleParameter['groups'];

        $roleNameAttr = $roleParameter['nameAttribute'];
        $filter = $roleParameter['filter'] ?? '';

        $entries = $this->driver->search(
            $roleParameter['baseDn'],
            sprintf('(&%s(%s=%s))', $filter, $roleParameter['userDnAttribute'], $entry['dn']),
            [$roleParameter['nameAttribute']]
        );

        $allowedRoles = [];
        foreach ($this->roles as $key => $value) {
            $allowedRoles[] = $key;
            foreach ($value as $name) {
                $allowedRoles[] = $name;
            }
        }
        $allowedRoles = array_unique($allowedRoles);

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

    private static function slugify($role)
    {
        $role = preg_replace('/\W+/', '_', $role);
        $role = trim($role, '_');
        $role = strtoupper($role);

        return $role;
    }
}
