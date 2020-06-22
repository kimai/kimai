<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class ProjectIdLoader implements LoaderInterface
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
        $qb->select('PARTIAL p.{id}', 'customer')
            ->from(Project::class, 'p')
            ->leftJoin('p.customer', 'customer')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL p.{id}', 'meta')
            ->from(Project::class, 'p')
            ->leftJoin('p.meta', 'meta')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL p.{id}', 'teams', 'teamlead')
            ->from(Project::class, 'p')
            ->leftJoin('p.teams', 'teams')
            ->leftJoin('teams.teamlead', 'teamlead')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL p.{id}', 'PARTIAL customer.{id}', 'teams', 'teamlead')
            ->from(Project::class, 'p')
            ->leftJoin('p.customer', 'customer')
            ->leftJoin('customer.teams', 'teams')
            ->leftJoin('teams.teamlead', 'teamlead')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->getQuery()
            ->execute();
    }
}
