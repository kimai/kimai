<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Permission;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Event\ApiTokenScopePermissionsEvent;
use App\Security\RolePermissionManager;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Central knowledge about the CRUD scope model used for API access tokens.
 *
 * A scope is a string "<resource>:<action>", e.g. "timesheet:read".
 *
 * This class only holds the ONE translation direction that is actually needed:
 * scope -> underlying Kimai role permission(s). It is used to decide
 *   a) which scopes may be offered to a user (§7.2 of the spec) and
 *   b) the effective "granted" flag of the GET /api/token endpoint (§8).
 *
 * The enforcement direction (§5) does NOT use this class - there a scope is a
 * plain set-membership check on the token (AccessToken::hasScope()).
 *
 * The mapping itself is not hard-coded here: it is collected via the
 * {@see ApiTokenScopePermissionsEvent}, so core (see
 * {@see \App\EventSubscriber\ApiTokenScopePermissionSubscriber}) and plugins
 * contribute the mapping for the resources they expose.
 */
final class ApiTokenScopes
{
    public const ACTION_CREATE = 'create';
    public const ACTION_READ = 'read';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    /**
     * All actions in a stable display order.
     *
     * @var array<string>
     */
    public const ACTIONS = [
        self::ACTION_CREATE,
        self::ACTION_READ,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
    ];

    private ?ApiTokenScopePermissionsEvent $permissions = null;

    public function __construct(
        private readonly RolePermissionManager $permissionManager,
        private readonly EventDispatcherInterface $dispatcher,
    )
    {
    }

    private function getPermissions(): ApiTokenScopePermissionsEvent
    {
        if ($this->permissions === null) {
            $event = new ApiTokenScopePermissionsEvent();
            $this->dispatcher->dispatch($event);
            $this->permissions = $event;
        }

        return $this->permissions;
    }

    public static function createScope(string $resource, string $action): string
    {
        return $resource . ':' . $action;
    }

    /**
     * Whether the given resource has an explicit permission mapping (i.e. it is
     * a known resource contributed by core or a plugin).
     */
    public function isMappedResource(string $resource): bool
    {
        return $this->getPermissions()->hasResource($resource);
    }

    /**
     * The role permissions a scope maps to, or null if the scope is unknown.
     *
     * @return array<string>|null
     */
    public function getMappedPermissions(string $resource, string $action): ?array
    {
        return $this->getPermissions()->getPermissionsFor($resource, $action);
    }

    /**
     * Whether the user is generally allowed to use the given scope, based on
     * their role permissions. Unknown scopes (e.g. from plugins) are not
     * restricted here and therefore return true.
     */
    public function userMayUseScope(User $user, string $resource, string $action): bool
    {
        $permissions = $this->getMappedPermissions($resource, $action);

        // unknown resource/action (e.g. plugin) -> not restricted by this class
        if ($permissions === null) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->permissionManager->hasRolePermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the effective scope matrix for a token (see GET /api/token, §8).
     *
     * @param array<string, array<string>> $catalog resource => list of existing actions (from the compiled route map)
     * @return array<string, array<string, bool>> resource => action => granted
     */
    public function getEffectiveMatrix(?AccessToken $token, User $user, array $catalog): array
    {
        $legacy = $token === null || $token->isLegacy();

        $matrix = [];
        foreach ($catalog as $resource => $actions) {
            foreach ($actions as $action) {
                if ($legacy) {
                    // legacy tokens run with full permissions -> everything true
                    $matrix[$resource][$action] = true;
                    continue;
                }

                $scope = self::createScope($resource, $action);
                $matrix[$resource][$action] =
                    $token->hasScope($scope) && $this->userMayUseScope($user, $resource, $action);
            }
        }

        return $matrix;
    }
}
