<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Entity\User;
use FR3D\LdapBundle\Hydrator\HydrateWithMapTrait;
use FR3D\LdapBundle\Hydrator\HydratorInterface;
use FR3D\LdapBundle\Model\LdapUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapUserHydrator implements HydratorInterface
{
    use HydrateWithMapTrait;

    /**
     * @var string[]
     */
    private $attributeMap;
    /**
     * @var string[]
     */
    private $roleMap;

    public function __construct(array $attributeMap)
    {
        $this->attributeMap = $attributeMap['attributes'];
        $this->roleMap = $attributeMap['groups'];
    }

    protected function createUser(): User
    {
        $user = new User();
        $user->setPassword('');
        $user->setEnabled(true);

        return $user;
    }

    public function hydrate(array $ldapEntry): UserInterface
    {
        $user = $this->createUser();
        $this->hydrateUser($user, $ldapEntry);

        return $user;
    }

    public function hydrateUser(User $user, array $ldapEntry)
    {
        $this->hydrateUserWithAttributesMap($user, $ldapEntry, $this->attributeMap);

        if ($user instanceof LdapUserInterface) {
            $user->setDn($ldapEntry['dn']);
        }

        // just a fallback to prevent Exceptions in case no email is available in LDAP
        if (null === $user->getEmail()) {
            $user->setEmail($user->getUsername());
        }

        if (count($this->roleMap) > 0) {
            $userGroups = $user->getLdapGroups();
            $roles = [];

            foreach ($this->roleMap as $attr) {
                if (!in_array($attr['ldap_value'], $userGroups)) {
                    continue;
                }
                $roles[] = $attr['role'];
            }
            $user->setRoles($roles);
        }
    }
}
