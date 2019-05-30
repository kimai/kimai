<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
