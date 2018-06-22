<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

/**
 * Can be used for advanced queries with the: UserRepository
 */
class UserQuery extends VisibilityQuery
{
    /**
     * @var string
     */
    protected $role;

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     * @return UserQuery
     */
    public function setRole($role)
    {
        if (false !== strpos($role, 'ROLE_') || null === $role) {
            $this->role = $role;
        }

        return $this;
    }
}
