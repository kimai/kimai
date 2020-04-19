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
            $isAllowed = $item['allowed'];

            // see permissions.html.twig for this special case
            if ($role === User::ROLE_SUPER_ADMIN && \in_array($perm, ['role_permissions', 'view_user'])) {
                continue;
            }

            if (!$isAllowed) {
                if (\array_key_exists($role, $this->permissions)) {
                    if (($key = array_search($perm, $this->permissions[$role])) !== false) {
                        unset($this->permissions[$role][$key]);
                    }
                }
            } else {
                if (!\array_key_exists($role, $this->permissions)) {
                    $this->permissions[$role] = [];
                }
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
