<?php

namespace App\Ldap;

use App\Entity\UserPreference;

trait LdapUserTrait
{
    /**
     * @var string[]
     */
    protected $ldapGroups;

    /**
     * Set Ldap Distinguished Name.
     *
     * @param string $dn Distinguished Name
     */
    public function setDn(string $dn)
    {
        $pref = $this->getPreference('ldap.dn');

        if (null === $pref) {
            $pref = (new UserPreference())
                ->setName('ldap.dn');
        }
        $pref->setValue($dn);

        $this->addPreference($pref);
    }

    /**
     * Get Ldap Distinguished Name.
     *
     * @return string|null Distinguished Name
     */
    public function getDn(): ?string
    {
        return $this->getPreferenceValue('ldap.dn');
    }

    /**
     * Sets the group from the LDAP.
     * As this is NOT the Kimai group, an EventListener will be used to convert them later on.
     *
     * FIXME create EventListener
     *
     * @param string $group
     */
    public function addLdapGroup(string $group)
    {
        $this->ldapGroups[] = $group;
    }
}
