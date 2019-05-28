<?php

namespace App\Ldap;

use App\Entity\UserPreference;

trait LdapUserTrait
{
    /**
     * Set Ldap Distinguished Name.
     *
     * @param string $dn Distinguished Name
     */
    public function setDn(string $dn)
    {
        $pref = (new UserPreference())
            ->setName('ldap.dn')
            ->setValue($dn);

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
}
