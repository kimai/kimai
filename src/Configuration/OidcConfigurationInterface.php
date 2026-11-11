<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

interface OidcConfigurationInterface
{
    /**
     * Whether OIDC login is activated.
     */
    public function isActivated(): bool;

    /**
     * Returns the title that is exclusively used in the frontend.
     * Currently, used to display the button in the login screen.
     */
    public function getTitle(): string;

    /**
     * Returns the provider name that is exclusively used in the frontend.
     * Currently, used to display an icon in the login screen.
     */
    public function getProvider(): ?string;

    /**
     * The OAuth2 / OIDC client ID registered at the identity provider.
     */
    public function getClientId(): string;

    /**
     * The OAuth2 / OIDC client secret registered at the identity provider.
     */
    public function getClientSecret(): string;

    /**
     * The issuer / base URL used to discover the OIDC endpoints via
     * "{issuer}/.well-known/openid-configuration". Can be empty when all
     * endpoints below are configured explicitly.
     */
    public function getIssuer(): ?string;

    public function getAuthorizationUrl(): ?string;

    public function getTokenUrl(): ?string;

    public function getUserInfoUrl(): ?string;

    /**
     * The scopes requested during login (e.g. "openid profile email").
     *
     * @return array<int, string>
     */
    public function getScopes(): array;

    /**
     * The claim that is used as the unique Kimai user identifier.
     */
    public function getUsernameClaim(): string;

    /**
     * Mapping of OIDC claims to Kimai user properties.
     *
     * @return array<int, array{oidc: string, kimai: string}>
     */
    public function getAttributeMapping(): array;

    /**
     * The claim that contains the user roles / groups.
     */
    public function getRolesClaim(): ?string;

    /**
     * Mapping of OIDC role/group values to Kimai roles.
     *
     * @return array<int, array{oidc: string, kimai: string}>
     */
    public function getRolesMapping(): array;

    /**
     * Whether the roles are reset on every login.
     */
    public function isRolesResetOnLogin(): bool;
}
