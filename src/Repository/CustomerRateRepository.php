<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerRate;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * @extends \Doctrine\ORM\EntityRepository<CustomerRate>
 */
class CustomerRateRepository extends EntityRepository
{
    public function saveRate(CustomerRate $rate)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rate);
        $entityManager->flush();
    }

    public function deleteRate(CustomerRate $rate)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($rate);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @param Customer $customer
     * @return CustomerRate[]
     */
    public function getRatesForCustomer(Customer $customer): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, c')
            ->from(CustomerRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.customer', 'c')
            ->andWhere(
                $qb->expr()->eq('r.customer', ':customer')
            )
            ->addOrderBy('u.alias')
            ->setParameter('customer', $customer)
        ;

        return $qb->getQuery()->getResult();
    }
}
