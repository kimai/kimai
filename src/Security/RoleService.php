<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Repository\RoleRepository;

final class RoleService
{
    /**
     * @var array<string>
     */
    private array $roleNames = [];
    private bool $isInitialized = false;

    /**
     * @param RoleRepository $repository
     * @param array<string> $roles as defined in security.yaml
     */
    public function __construct(private RoleRepository $repository, private array $roles)
    {
    }

    /**
     * Returns a list of UPPERCASE role names.
     *
     * @return string[]
     */
    public function getAvailableNames(): array
    {
        if (!$this->isInitialized) {
            $roles = [];
            foreach ($this->repository->findAll() as $item) {
                if ($item->getName() === null) {
                    continue;
                }
                $roles[] = strtoupper($item->getName());
            }

            $this->roleNames = array_values(array_unique(array_merge($this->roles, $roles)));
            $this->isInitialized = true;
        }

        return $this->roleNames;
    }

    public function getSystemRoles(): array
    {
        return $this->roles;
    }
}
