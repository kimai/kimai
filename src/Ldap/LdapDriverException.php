<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

class LdapDriverException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
