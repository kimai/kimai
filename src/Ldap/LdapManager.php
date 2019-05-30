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
use FR3D\LdapBundle\Driver\LdapDriverInterface;
use FR3D\LdapBundle\Hydrator\HydratorInterface;
use FR3D\LdapBundle\Ldap\LdapManager as FR3DLdapManager;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Overwritten:
 * - so it can be deactivated via config switch
 * - for attribute and role sync on every login
 */
class LdapManager extends FR3DLdapManager
{
    /**
     * @var LdapConfiguration
     */
    protected $config;
    /**
     * @var bool
     */
    protected $activated = false;
    /**
     * @var array
     */
    protected $roles = [];

    public function __construct(LdapDriverInterface $driver, HydratorInterface $hydrator, array $roles, LdapConfiguration $config)
    {
        parent::__construct($driver, $hydrator, $config->getUserParameters());
        $this->activated = $config->isActivated();
        $this->roles = $roles;
        $this->config = $config;
    }

    /**
     * @param User $user
     * @param string $username
     * @throws \Exception
     * @throws \FR3D\LdapBundle\Driver\LdapDriverException
     */
    public function updateUser(User $user, $username)
    {
        if (!$this->activated) {
            return;
        }

        $filter = $this->buildFilter([$this->params['usernameAttribute'] => $username]);
        $entries = $this->driver->search($this->params['baseDn'], $filter);

        if ($entries['count'] > 1) {
            throw new \Exception('This search can only return a single user');
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
     * @throws \FR3D\LdapBundle\Driver\LdapDriverException
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

    public function findUserByUsername(string $username): ?UserInterface
    {
        if (!$this->activated) {
            return null;
        }

        return parent::findUserByUsername($username);
    }

    public function findUserBy(array $criteria): ?UserInterface
    {
        if (!$this->activated) {
            return null;
        }

        return parent::findUserBy($criteria);
    }

    public function bind(UserInterface $user, string $password): bool
    {
        if (!$this->activated) {
            return false;
        }

        return parent::bind($user, $password);
    }
}
