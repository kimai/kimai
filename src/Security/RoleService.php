<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

final class RoleService
{
    /**
     * @var array
     */
    private $roles;
    /**
     * @var string[]
     */
    private $roleNames = [];

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function getAvailableNames(): array
    {
        if (empty($this->roleNames)) {
            $roles = [];
            foreach ($this->roles as $key => $value) {
                $roles[] = $key;
                if (is_array($value)) {
                    foreach ($value as $name) {
                        $roles[] = $name;
                    }
                }
            }

            $this->roleNames = array_values(array_unique($roles));
        }

        return $this->roleNames;
    }
}
