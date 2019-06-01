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
class LdapUserHydrator
{
    /**
     * @var string[]
     */
    private $attributeMap;

    public function __construct(LdapConfiguration $config)
    {
        $attributeMap = $config->getUserParameters();

        $this->attributeMap = $attributeMap['attributes'];
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

        // just a fallback to prevent Exceptions in case no email is available in LDAP
        if (null === $user->getEmail()) {
            $user->setEmail($user->getUsername());
        }
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
