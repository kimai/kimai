<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Collects the mapping "API scope -> underlying Kimai role permission(s)".
 *
 * This mapping is only used when granting or displaying API-token scopes (the
 * token form and the GET /api/token endpoint) to decide whether a user may
 * actually use a scope. It is NOT used for enforcement.
 *
 * Core registers its mapping via {@see \App\EventSubscriber\ApiTokenScopePermissionSubscriber};
 * plugins can subscribe to this event and add the mapping for the resources
 * they expose through their own API controllers (#[ApiToken('...')]).
 */
final class ApiTokenScopePermissionsEvent extends Event
{
    /**
     * @var array<string, array<string, array<string>>> resource => action => role permissions
     */
    private array $permissions = [];

    /**
     * Maps an API scope (resource + action) to the role permission(s) that a
     * user must hold (at least one of them) to be allowed to use that scope.
     *
     * Calling this multiple times for the same scope merges the permissions.
     *
     * @param array<string> $permissions
     */
    public function addPermission(string $resource, string $action, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->permissions[$resource][$action][] = $permission;
        }

        $this->permissions[$resource][$action] = array_values(array_unique($this->permissions[$resource][$action] ?? []));
    }

    /**
     * @return array<string> the mapped permissions, or null if the scope is unknown
     */
    public function getPermissionsFor(string $resource, string $action): ?array
    {
        return $this->permissions[$resource][$action] ?? null;
    }

    public function hasResource(string $resource): bool
    {
        return \array_key_exists($resource, $this->permissions);
    }

    /**
     * @return array<string, array<string, array<string>>>
     */
    public function all(): array
    {
        return $this->permissions;
    }
}
