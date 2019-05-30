<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

trait LdapUserTrait
{
    /**
     * @var string[]
     */
    protected $ldapGroups = [];

    /**
     * Set Ldap Distinguished Name.
     *
     * @param string $dn
     */
    public function setDn(string $dn)
    {
        $this->setPreferenceValue('ldap.dn', $dn);
    }

    /**
     * Get Ldap Distinguished Name.
     *
     * @return string|null
     */
    public function getDn(): ?string
    {
        return $this->getPreferenceValue('ldap.dn');
    }

    public function isLdapUser(): bool
    {
        return null !== $this->getDn();
    }

    /**
     * Sets the group from the LDAP.
     * Only used to sync LDAP groups during hydration.
     *
     * @param string|array $group
     */
    public function addLdapGroup($group)
    {
        if (null === $group) {
            return;
        }

        if (!is_array($group)) {
            $group = [$group];
        }
        $this->ldapGroups = array_merge($this->ldapGroups, $group);
    }

    public function getLdapGroups(): array
    {
        return $this->ldapGroups;
    }
}
