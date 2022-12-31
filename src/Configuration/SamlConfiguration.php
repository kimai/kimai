<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @CloudRequired
 */
final class SamlConfiguration implements SamlConfigurationInterface
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function isActivated(): bool
    {
        return $this->configuration->isSamlActive();
    }

    public function getTitle(): string
    {
        return $this->configuration->getSamlTitle();
    }

    public function getProvider(): string
    {
        return $this->configuration->getSamlProvider();
    }

    public function getAttributeMapping(): array
    {
        return $this->configuration->findArray('saml.mapping');
    }

    public function getRolesAttribute(): ?string
    {
        $attr = $this->configuration->find('saml.roles.attribute');
        if (empty($attr)) {
            return null;
        }

        return (string) $attr;
    }

    public function getRolesMapping(): array
    {
        return $this->configuration->findArray('saml.roles.mapping');
    }

    public function isRolesResetOnLogin(): bool
    {
        return $this->configuration->isSamlRolesResetOnLogin();
    }

    public function getConnection(): array
    {
        return $this->configuration->findArray('saml.connection');
    }
}
