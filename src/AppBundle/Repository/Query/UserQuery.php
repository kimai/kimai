<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository\Query;

/**
 * Can be used for advanced queries with the: UserRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

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
        if (strpos($role, 'ROLE_') !== false || $role === null) {
            $this->role = $role;
        }
        return $this;
    }
}
