<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Customer;
use App\Entity\Team;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\CustomerQueryHydrate;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Customer>
 */
final class CustomerLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerQuery $query
    )
    {
    }

    /**
     * @param array<Customer> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        $customerIds = array_filter(array_unique(array_map(function (Customer $customer) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $customer->getName();

            return $customer->getId();
        }, $results)), function ($value) { return $value !== null; });

        $hydrateTeams = false;
        $hydrateTeamMembers = false;

        foreach ($this->query->getHydrate() as $hydrate) {
            switch ($hydrate) {
                case CustomerQueryHydrate::TEAMS:
                    $hydrateTeams = true;
                    break;
                case CustomerQueryHydrate::TEAM_MEMBER:
                    $hydrateTeams = true;
                    $hydrateTeamMembers = true;
                    break;
            }
        }

        if (!$hydrateTeams) {
            return;
        }

        $em = $this->entityManager;

        // required where we need to check team permissions, e.g. "Customer listing"
        if (\count($customerIds) > 0) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL c.{id}', 'teams')
                ->from(Customer::class, 'c')
                ->leftJoin('c.teams', 'teams')
                ->andWhere($qb->expr()->in('c.id', $customerIds))
                ->getQuery()
                ->execute();
        }

        // do not load team members or leads by default, because they will only be used on detail pages
        if ($hydrateTeamMembers) {
            $teamIds = [];
            foreach ($results as $customer) {
                foreach ($customer->getTeams() as $team) {
                    $teamIds[] = $team->getId();
                }
            }
            $teamIds = array_unique($teamIds);

            if (\count($teamIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL team.{id}', 'members', 'user')
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
