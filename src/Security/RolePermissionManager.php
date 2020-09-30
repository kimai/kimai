<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use App\Repository\RolePermissionRepository;

final class RolePermissionManager
{
    /**
     * Permissions that are always true for ROLE_SUPER_ADMIN, no matter what is inside the database.
     *
     * @var string[]
     */
    public const SUPER_ADMIN_PERMISSIONS = [
        'view_all_data',
        'role_permissions',
        'view_user'
    ];

    /**
     * @var array
     */
    private $permissions = [];
    /**
     * @var string[]
     */
    private $knownPermissions = [];

    public function __construct(RolePermissionRepository $repository, array $permissions)
    {
        $this->permissions = $permissions;

        foreach ($permissions as $role => $perms) {
            $this->knownPermissions = array_merge($this->knownPermissions, $perms);
        }
        $this->knownPermissions = array_unique($this->knownPermissions);

        $all = $repository->getAllAsArray();
        foreach ($all as $item) {
            $perm = $item['permission'];
            $role = strtoupper($item['role']);
            $isAllowed = (bool) $item['allowed'];

            // these permissions may not be revoked at any time, because super admin would loose the ability to reactivate any permission
            if ($role === User::ROLE_SUPER_ADMIN && \in_array($perm, self::SUPER_ADMIN_PERMISSIONS)) {
                continue;
            }

            if (!\array_key_exists($role, $this->permissions)) {
                $this->permissions[$role] = [];
            }

            if (false === $isAllowed) {
                if (($key = array_search($perm, $this->permissions[$role])) !== false) {
                    unset($this->permissions[$role][$key]);
                }
            } else {
                $this->permissions[$role][] = $perm;
            }
        }
    }

    /**
     * Only permissions which were registered through the Symfony configuration stack will be acknowledged here.
     *
     * @param string $permission
     * @return bool
     */
    public function isRegisteredPermission(string $permission): bool
    {
        return \in_array($permission, $this->knownPermissions);
    }

    public function hasPermission(string $role, string $permission): bool
    {
        $role = strtoupper($role);

        if (!isset($this->permissions[$role])) {
            return false;
        }

        return \in_array($permission, $this->permissions[$role]);
    }

    /**
     * Only permissions which were registered through the Symfony configuration stack will be returned here.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->knownPermissions;
    }
}
