<?php

namespace App\Ldap;

use App\Entity\User;
use FR3D\LdapBundle\Hydrator\AbstractHydrator;
use FR3D\LdapBundle\Hydrator\HydratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapUserHydrator extends AbstractHydrator implements HydratorInterface
{
    /**
     * @return User
     */
    protected function createUser()
    {
        $user = new User();
        $user->setPassword('');
        $user->setEnabled(true);

        return $user;
    }

    public function hydrate(array $ldapEntry): UserInterface
    {
        /** @var User $user */
        $user = parent::hydrate($ldapEntry);

        // just a fallback to prevent Exceptions in case no email is available in LDAP
        if (null === $user->getEmail()) {
            $user->setEmail($user->getUsername());
        }

        return $user;
    }
}
