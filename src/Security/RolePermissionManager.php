<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

class RolePermissionManager
{
    /**
     * @var array
     */
    protected $permissions = [];
    /**
     * @var string[]
     */
    protected $knownPermissions = [];

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;

        foreach ($permissions as $role => $perms) {
            $this->knownPermissions = array_merge($this->knownPermissions, $perms);
        }
        $this->knownPermissions = array_unique($this->knownPermissions);
    }

    public function isRegisteredPermission(string $permission): bool
    {
        return in_array($permission, $this->knownPermissions);
    }

    public function hasPermission(string $role, string $permission): bool
    {
        if (!isset($this->permissions[$role])) {
            return false;
        }

        return in_array($permission, $this->permissions[$role]);
    }

    public function getRoles(): array
    {
        return array_keys($this->permissions);
    }

    public function getPermissions(): array
    {
        return $this->knownPermissions;
    }
}
