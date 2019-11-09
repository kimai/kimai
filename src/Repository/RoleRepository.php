<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Role;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

class RoleRepository extends EntityRepository
{
    /**
     * @return Role[]
     */
    public function findAll()
    {
        return parent::findAll();
    }

    public function saveRole(Role $role)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($role);
        $entityManager->flush();
    }

    public function deleteRole(Role $role)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($role);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }
}
