<?php

namespace App\Ldap;

use Zend\Ldap\Ldap;

/**
 * Overwritten to prevent errors in case:
 * LDAP is deactivated and LDAP extension is not loaded
 */
class ZendLdap extends Ldap
{
    public function __construct($options = [], bool $activated = false)
    {
        if ($activated) {
            parent::__construct($options);
        }
    }
}
