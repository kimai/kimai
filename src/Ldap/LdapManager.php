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

    public function __construct(LdapDriver $driver, LdapUserHydrator $hydrator, LdapConfiguration $config)
    {
        $this->params = $config->getUserParameters();
        $this->config = $config;
        $this->driver = $driver;
        $this->hydrator = $hydrator;
    }

    /**
     * Only executed for unknown local users.
     *
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
dump($entries);exit;
        $user = $this->hydrator->hydrate($entries[0]);

        // do not updateUser() here, as zthis would happen before bind()
        //$this->updateUser($user);

        return $user;
    }

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
     * @throws LdapDriverException
     */
    public function updateUser(User $user)
    {
        $baseDn = $user->getPreferenceValue('ldap.dn');
        $filter = '(objectClass=*)';

        if (null === $baseDn) {
            throw new LdapDriverException('This account is not a registered LDAP user');
        }

        $entries = $this->driver->search($baseDn, $filter);

        if ($entries['count'] > 1) {
            throw new LdapDriverException('This search must only return a single user');
        }

        if (0 === $entries['count']) {
            return;
        }

        $this->hydrator->hydrateUser($user, $entries[0]);

        $roleParameter = $this->config->getRoleParameters();
        if (null === $roleParameter['baseDn']) {
            return;
        }

        $roles = $this->getRoles($entries[0]['dn'], $roleParameter);

        if (!empty($roles)) {
            $this->hydrator->hydrateRoles($user, $roles);
        }
    }

    protected function getRoles(string $dn, array $roleParameter): array
    {
        $filter = $roleParameter['filter'] ?? '';

        return $this->driver->search(
            $roleParameter['baseDn'],
            sprintf('(&%s(%s=%s))', $filter, $roleParameter['userDnAttribute'], $dn),
            [$roleParameter['nameAttribute']]
        );
    }
}
