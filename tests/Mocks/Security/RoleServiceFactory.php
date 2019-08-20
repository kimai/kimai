<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Security;

use App\Entity\User;
use App\Security\RoleService;
use App\Tests\Mocks\AbstractMockFactory;

class RoleServiceFactory extends AbstractMockFactory
{
    public function create($roles = null): RoleService
    {
        if (null === $roles) {
            $roles = [
                User::ROLE_USER => [],
                User::ROLE_TEAMLEAD => [User::ROLE_USER],
                User::ROLE_ADMIN => [User::ROLE_TEAMLEAD],
                User::ROLE_SUPER_ADMIN => [User::ROLE_ADMIN],
            ];
        }

        return new RoleService($roles);
    }
}
