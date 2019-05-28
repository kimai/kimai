<?php

namespace App\Ldap;

use FR3D\LdapBundle\Ldap\LdapManager as FR3DLdapManager;
use FR3D\LdapBundle\Ldap\LdapManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Own implementation, to be able to deactivate LDAP via config switch.
 */
class LdapManager implements LdapManagerInterface
{
    /**
     * @var FR3DLdapManager
     */
    protected $manager;
    /**
     * @var bool
     */
    protected $activated = false;

    public function __construct(FR3DLdapManager $manager, bool $active)
    {
        $this->manager = $manager;
        $this->activated = $active;
    }

    public function findUserByUsername(string $username): ?UserInterface
    {
        if (!$this->activated) {
            return null;
        }

        return $this->manager->findUserByUsername($username);
    }

    public function findUserBy(array $criteria): ?UserInterface
    {
        if (!$this->activated) {
            return null;
        }

        return $this->manager->findUserBy($criteria);
    }

    public function bind(UserInterface $user, string $password): bool
    {
        if (!$this->activated) {
            return false;
        }

        return $this->manager->bind($user, $password);
    }
}
