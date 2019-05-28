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
}
