<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
class SanitizingException extends \Exception
{
    protected $actualException;
    protected $secret;

    public function __construct(\Exception $actualException, $secret)
    {
        parent::__construct(
            $this->stripSecret($actualException->getMessage(), $secret),
            $actualException->getCode()
        );

        $this->actualException = $actualException;
        $this->secret = $secret;
    }

    protected function stripSecret(string $message, string $secret)
    {
        return str_replace($secret, '****', $message);
    }

    public function __toString()
    {
        return $this->stripSecret($this->actualException->__toString(), $this->secret);
    }
}
