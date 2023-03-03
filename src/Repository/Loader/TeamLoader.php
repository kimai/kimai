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

final class TeamLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<int|Team> $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $ids = array_map(function ($team) {
            if ($team instanceof Team) {
                // make sure that this potential doctrine proxy is initialized and filled with all data
                $team->getName();

                return $team->getId();
            }

            return $team;
        }, $results);

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL team.{id}', 'members', 'user')
            ->from(Team::class, 'team')
            ->leftJoin('team.members', 'members')
            ->leftJoin('members.user', 'user')
            ->andWhere($qb->expr()->in('team.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL team.{id}', 'projects')
            ->from(Team::class, 'team')
            ->leftJoin('team.projects', 'projects')
            ->andWhere($qb->expr()->in('team.id', $ids))
            ->getQuery()
            ->execute();
    }
}
