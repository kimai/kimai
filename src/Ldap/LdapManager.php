<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Entity\User;
use FR3D\LdapBundle\Driver\LdapDriverInterface;
use FR3D\LdapBundle\Hydrator\HydratorInterface;
use FR3D\LdapBundle\Ldap\LdapManager as FR3DLdapManager;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapManager extends FR3DLdapManager
{
    /**
     * @var bool
     */
    protected $activated = false;

    public function __construct(LdapDriverInterface $driver, HydratorInterface $hydrator, array $params, bool $activated)
    {
        parent::__construct($driver, $hydrator, $params);
        $this->activated = $activated;
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
        }
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
