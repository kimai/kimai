<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Role;
use App\Entity\RolePermission;
use Doctrine\ORM\EntityRepository;

/**
 * @extends \Doctrine\ORM\EntityRepository<RolePermission>
 */
class RolePermissionRepository extends EntityRepository
{
    public function saveRolePermission(RolePermission $permission)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($permission);
        $entityManager->flush();
    }

    public function findRolePermission(Role $role, string $permission): ?RolePermission
    {
        return $this->findOneBy(['role' => $role, 'permission' => $permission]);
    }

    /**
     * @return array<array<string, string|bool>>
     */
    public function getAllAsArray(): array
    {
        $qb = $this->createQueryBuilder('rp');

        $qb->select('r.name as role,rp.permission,rp.allowed')
            ->leftJoin('rp.role', 'r');

        return $qb->getQuery()->getArrayResult(); // @phpstan-ignore-line
    }
}
