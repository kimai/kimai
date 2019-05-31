<?php

namespace App\Ldap;

class LdapDriverException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
