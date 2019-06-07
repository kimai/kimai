<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

class RoleService
{
    /**
     * @var array
     */
    protected $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function getAvailableNames(): array
    {
        $roles = [];
        foreach ($this->roles as $key => $value) {
            $roles[] = $key;
            if (is_array($value)) {
                foreach ($value as $name) {
                    $roles[] = $name;
                }
            }
        }

        return array_values(array_unique($roles));
    }
}
