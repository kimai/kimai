<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class UserIdLoader implements LoaderInterface
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
        /** @var User[] $users */
        $users = $qb->select('PARTIAL u.{id}', 'teams')
            ->from(User::class, 'u')
            ->leftJoin('u.teams', 'teams')
            ->andWhere($qb->expr()->in('u.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL u.{id}', 'preferences')
            ->from(User::class, 'u')
            ->leftJoin('u.preferences', 'preferences')
            ->andWhere($qb->expr()->in('u.id', $ids))
            ->getQuery()
            ->execute();

        $teamIds = [];
        foreach ($users as $user) {
            foreach ($user->getTeams() as $team) {
                $teamIds[] = $team->getId();
            }
        }

        if (\count($teamIds) > 0) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL t.{id}', 'teamlead')
                ->from(Team::class, 't')
                ->leftJoin('t.teamlead', 'teamlead')
                ->andWhere($qb->expr()->in('t.id', $teamIds))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL t.{id}', 'users')
                ->from(Team::class, 't')
                ->leftJoin('t.users', 'users')
                ->andWhere($qb->expr()->in('t.id', $teamIds))
                ->getQuery()
                ->execute();
        }
    }
}
