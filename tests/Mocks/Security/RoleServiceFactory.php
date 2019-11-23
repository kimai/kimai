<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Security;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Security\RoleService;
use App\Tests\Mocks\AbstractMockFactory;

class RoleServiceFactory extends AbstractMockFactory
{
    /**
     * @param string[]|null $roles
     * @param Role[]|null $repositoryRoles
     * @return RoleService
     */
    public function create(?array $roles = null, ?array $repositoryRoles = []): RoleService
    {
        if (null === $roles) {
            $roles = [
                User::ROLE_USER => [],
                User::ROLE_TEAMLEAD => [User::ROLE_USER],
                User::ROLE_ADMIN => [User::ROLE_TEAMLEAD],
                User::ROLE_SUPER_ADMIN => [User::ROLE_ADMIN],
            ];
        }

        $repository = $this->getMockBuilder(RoleRepository::class)->onlyMethods(['findAll'])->disableOriginalConstructor()->getMock();
        $repository->method('findAll')->willReturn($repositoryRoles);

        /* @var RoleRepository $repository */
        return new RoleService($repository, $roles);
    }
}
