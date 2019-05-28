<?php

namespace App\Ldap;

use FR3D\LdapBundle\Driver\ZendLdapDriver;
use Psr\Log\LoggerInterface;

/**
 * Overwritten, so we can force the DI container to load the ZendLdap class,
 * instead of the default version (which would throw an exception on systems without ldap extension).
 */
class LdapDriver extends ZendLdapDriver
{
    public function __construct(ZendLdap $driver, LoggerInterface $logger = null)
    {
        parent::__construct($driver, $logger);
    }
}
