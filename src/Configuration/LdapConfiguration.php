<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

final class LdapConfiguration
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function isActivated(): bool
    {
        return $this->configuration->isLdapActive();
    }

    public function getRoleParameters(): array
    {
        return $this->configuration->findArray('ldap.role');
    }

    public function getUserParameters(): array
    {
        return $this->configuration->findArray('ldap.user');
    }

    public function getConnectionParameters(): array
    {
        return $this->configuration->findArray('ldap.connection');
    }
}
