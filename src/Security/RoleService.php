<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\Role;
use App\Repository\RoleRepository;

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
    /**
     * @var RoleRepository
     */
    private $repository;

    public function __construct(RoleRepository $repository, array $roles)
    {
        $this->repository = $repository;
        $this->roles = $roles;
    }

    private function cacheNames()
    {
        if (empty($this->roleNames)) {
            $roles = [];
            foreach ($this->roles as $key => $value) {
                $roles[] = $key;
                if (\is_array($value)) {
                    foreach ($value as $name) {
                        $roles[] = $name;
                    }
                }
            }

            /** @var Role $item */
            foreach ($this->repository->findAll() as $item) {
                $roles[] = $item->getName();
            }

            $this->roleNames = array_values(array_unique($roles));
        }
    }

    public function getAvailableNames(): array
    {
        $this->cacheNames();

        return $this->roleNames;
    }

    public function getSystemRoles(): array
    {
        return $this->roles;
    }
}
