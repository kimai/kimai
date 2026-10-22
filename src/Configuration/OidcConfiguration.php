<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

final class OidcConfiguration implements OidcConfigurationInterface
{
    public function __construct(private SystemConfiguration $configuration) {}

    public function isActivated(): bool
    {
        return $this->configuration->isOidcActive();
    }

    public function getTitle(): string
    {
        return $this->configuration->getOidcTitle();
    }

    public function getProviderUrl(): string
    {
        return $this->configuration->getOidcProviderUrl();
    }

    public function getClientId(): string
    {
        return $this->configuration->getOidcClientId();
    }

    public function getClientSecret(): string
    {
        return $this->configuration->getOidcClientSecret();
    }

    public function isRolesResetOnLogin(): bool
    {
        return $this->configuration->isOidcRolesResetOnLogin();
    }

    public function getRolesMapping(): array
    {
        return $this->configuration->getOidcRolesMapping();
    }
}
