<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerIdLoader implements LoaderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int[] $ids
     */
    public function loadResults(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL c.{id}', 'meta')
            ->from(Customer::class, 'c')
            ->leftJoin('c.meta', 'meta')
            ->andWhere($qb->expr()->in('c.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL c.{id}', 'teams', 'teamlead')
            ->from(Customer::class, 'c')
            ->leftJoin('c.teams', 'teams')
            ->leftJoin('teams.teamlead', 'teamlead')
            ->andWhere($qb->expr()->in('c.id', $ids))
            ->getQuery()
            ->execute();
    }
}
