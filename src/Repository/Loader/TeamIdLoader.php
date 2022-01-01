<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class TeamIdLoader implements LoaderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int[] $teamIds
     */
    public function loadResults(array $teamIds): void
    {
        if (empty($teamIds)) {
            return;
        }

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL team.{id}', 'members', 'user')
            ->from(Team::class, 'team')
            ->leftJoin('team.members', 'members')
            ->leftJoin('members.user', 'user')
            ->andWhere($qb->expr()->in('team.id', $teamIds))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL team.{id}', 'projects')
            ->from(Team::class, 'team')
            ->leftJoin('team.projects', 'projects')
            ->andWhere($qb->expr()->in('team.id', $teamIds))
            ->getQuery()
            ->execute();
    }
}
