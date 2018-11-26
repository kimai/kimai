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
     * @var array
     */
    protected $knownPermissions = [];

    /**
     * @param array $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;

        foreach ($permissions as $role => $perms) {
            $this->knownPermissions = array_merge($this->knownPermissions, $perms);
        }
        $this->knownPermissions = array_unique($this->knownPermissions);
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function isRegisteredPermission($permission)
    {
        return in_array($permission, $this->knownPermissions);
    }

    /**
     * @param string $role
     * @return bool
     */
    public function roleHasPermission($role)
    {
        return isset($this->permissions[$role]);
    }

    /**
     * @param string $role
     * @param string $permission
     * @return bool
     */
    public function hasPermission($role, $permission)
    {
        if (!isset($this->permissions[$role])) {
            return false;
        }

        return in_array($permission, $this->permissions[$role]);
    }
}
