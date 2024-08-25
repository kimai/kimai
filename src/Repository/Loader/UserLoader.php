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
 * @implements LoaderInterface<User>
 */
final class UserLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $fullyHydrated = false
    )
    {
    }

    /**
     * @param array<User> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        $userIds = array_filter(array_unique(array_map(function (User $user) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $user->getDisplayName();

            return $user->getId();
        }, $results)), function ($value) { return $value !== null; });

        $em = $this->entityManager;

        // this is currently needed, as it does not work via the Doctrine eager fetch method
        // on user listing pages, if users are already in the unit of work from another load
        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL user.{id}', 'preferences')
            ->from(User::class, 'user')
            ->leftJoin('user.preferences', 'preferences')
            ->andWhere($qb->expr()->in('user.id', $userIds))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        /** @var User[] $users */
        $users = $qb->select('PARTIAL user.{id}', 'memberships', 'team')
            ->from(User::class, 'user')
            ->leftJoin('user.memberships', 'memberships')
            ->leftJoin('memberships.team', 'team')
            ->andWhere($qb->expr()->in('user.id', $userIds))
            ->getQuery()
            ->execute();

        // do not load team members or leads by default, because they will only be used on detail pages
        // and there is no benefit in adding multiple queries for most requests when they are only needed in one place
        if ($this->fullyHydrated) {
            $teamIds = [];
            foreach ($users as $user) {
                foreach ($user->getTeams() as $team) {
                    $teamIds[] = $team->getId();
                }
            }
            $teamIds = array_unique($teamIds);

            if (\count($teamIds) > 0) {
                $qb = $em->createQueryBuilder();
                /** @var Team[] $teams */
                $teams = $qb->select('PARTIAL team.{id}', 'members', 'user')
                    ->from(Team::class, 'team')
                    ->leftJoin('team.members', 'members')
                    ->leftJoin('members.user', 'user')
                    ->andWhere($qb->expr()->in('team.id', $teamIds))
                    ->getQuery()
                    ->execute();
            }
        }
    }
}
