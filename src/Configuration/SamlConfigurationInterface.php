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
interface SamlConfigurationInterface
{
    /**
     * Whether SAML login is activated.
     *
     * @return bool
     */
    public function isActivated(): bool;

    /**
     * Returns the title  that is exclusively used in the frontend.
     * Currently, used to display the button in the login screen.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the provider name that is exclusively used in the frontend.
     * Currently, used to display an icon in the login screen.
     *
     * @return string
     */
    public function getProvider(): string;

    public function getAttributeMapping(): array;

    public function getRolesAttribute(): ?string;

    public function getRolesMapping(): array;

    public function isRolesResetOnLogin(): bool;

    public function getConnection(): array;
}
