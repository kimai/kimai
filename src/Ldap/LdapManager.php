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
     * @return User|null
     * @throws \Exception
     */
    public function findUserByUsername(string $username): ?UserInterface
    {
        return $this->findUserBy([$this->params['usernameAttribute'] => $username]);
    }

    /**
     * @param array $criteria
     * @return User|null
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

        // do not updateUser() here, as this would happen before bind()
        return $this->hydrator->hydrate($entries[0]);
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
        $userFresh = $this->findUserByUsername($user->getUsername());
        if (null === $userFresh || null === ($baseDn = $userFresh->getPreferenceValue('ldap.dn'))) {
            throw new LdapDriverException(sprintf('Failed fetching user DN for %s', $user->getUsername()));
        }
        $user->setPreferenceValue('ldap.dn', $baseDn);

        $entries = $this->driver->search($baseDn, $this->params['attributesFilter']);

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
            $this->hydrator->hydrateRoles($user, $roles);
        }
    }

    protected function getRoles(string $dn, array $roleParameter): array
    {
        $filter = $roleParameter['filter'] ?? '';

        return $this->driver->search(
            $roleParameter['baseDn'],
            sprintf('(&%s(%s=%s))', $filter, $roleParameter['userDnAttribute'], ldap_escape($dn, '', LDAP_ESCAPE_FILTER)),
            [$roleParameter['nameAttribute']]
        );
    }
}
