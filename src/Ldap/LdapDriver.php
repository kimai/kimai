<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use FR3D\LdapBundle\Driver\ZendLdapDriver;

class LdapDriver extends ZendLdapDriver
{
    /**
     * Overwritten, so we can add the operational search attributes, see:
     * https://github.com/Maks3w/FR3DLdapBundle/issues/75
     *
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @return array|bool
     * @throws \FR3D\LdapBundle\Driver\LdapDriverException
     */
    public function search(string $baseDn, string $filter, array $attributes = [])
    {
        $attributes = array_unique(array_merge($attributes, ['+', '*']));

        return parent::search($baseDn, $filter, $attributes);
    }
}
