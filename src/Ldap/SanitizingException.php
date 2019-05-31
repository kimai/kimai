<?php

namespace App\Ldap;

class SanitizingException extends \Exception
{
    protected $actualException;
    protected $secret;

    public function __construct($actualException, $secret)
    {
        $this->actualException = $actualException;
        $this->secret = $secret;
    }

    public function __toString()
    {
        return str_replace($this->secret, '****', $this->actualException->__toString());
    }
}
