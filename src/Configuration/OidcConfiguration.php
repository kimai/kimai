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
    public function __construct(private readonly SystemConfiguration $configuration)
    {
    }

    public function isActivated(): bool
    {
        return $this->configuration->isOidcActive();
    }

    public function getTitle(): string
    {
        return $this->configuration->getOidcTitle();
    }

    public function getProvider(): ?string
    {
        return $this->configuration->getOidcProvider();
    }

    public function getClientId(): string
    {
        return $this->configuration->getOidcClientId();
    }

    public function getClientSecret(): string
    {
        return $this->configuration->getOidcClientSecret();
    }

    public function getIssuer(): ?string
    {
        return $this->configuration->getOidcIssuer();
    }

    public function getAuthorizationUrl(): ?string
    {
        return $this->configuration->getOidcAuthorizationUrl();
    }

    public function getTokenUrl(): ?string
    {
        return $this->configuration->getOidcTokenUrl();
    }

    public function getUserInfoUrl(): ?string
    {
        return $this->configuration->getOidcUserInfoUrl();
    }

    public function getScopes(): array
    {
        return $this->configuration->getOidcScopes();
    }

    public function getUsernameClaim(): string
    {
        return $this->configuration->getOidcUsernameClaim();
    }

    public function getAttributeMapping(): array
    {
        return $this->configuration->getOidcAttributeMapping();
    }

    public function getRolesClaim(): ?string
    {
        return $this->configuration->getOidcRolesClaim();
    }

    public function getRolesMapping(): array
    {
        return $this->configuration->getOidcRolesMapping();
    }

    public function isRolesResetOnLogin(): bool
    {
        return $this->configuration->isOidcRolesResetOnLogin();
    }
}
