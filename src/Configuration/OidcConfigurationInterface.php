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
interface OidcConfigurationInterface
{
    /**
     * Whether OIDC login is activated.
     *
     * @return bool
     */
    public function isActivated(): bool;

    /**
     * Returns the title that is displayed in the button on the login screen.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the provider URL. This URL will be used to find the "well known"
     * URL for the authorization server to discover all the settings required for
     * OIDC to work.
     *
     * @return string
     */
    public function getProviderUrl(): string;

    /**
     * Returns the client id
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * Returns the client secret
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Returns true if the groups should be reset on each login
     *
     * @return bool
     */
    public function isRolesResetOnLogin(): bool;

    /**
     * Returns the mapping from the oidc to the kimai groups
     *
     * @return array<int, array<'oidc'|'kimai', string>>
     */
    public function getRolesMapping(): array;
}
