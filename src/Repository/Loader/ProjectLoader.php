<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Project>
 */
final class ProjectLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $hydrateTeamMembers = false,
        private readonly bool $hydrateTeams = true
    )
    {
    }

    /**
     * @param array<Project> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        /** @var array<int> $projectIds */
        $projectIds = array_filter(array_unique(array_map(function (Project $project) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $project->getName();
            // using reporting controller tests will show that error
            $project->getCustomer()?->getName();

            return $project->getId();
        }, $results)), function ($value) { return $value !== null; });

        $em = $this->entityManager;

        if ($this->hydrateTeams && \count($projectIds) > 0) {
            $customerIds = array_filter(array_unique(array_map(function (Project $project) {
                return $project->getCustomer()->getId();
            }, $results)), function ($value) { return $value !== null; });

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'teams')
                ->from(Project::class, 'project')
                ->leftJoin('project.teams', 'teams')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();

            if (\count($customerIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL customer.{id}', 'teams')
                    ->from(Customer::class, 'customer')
                    ->leftJoin('customer.teams', 'teams')
                    ->andWhere($qb->expr()->in('customer.id', $customerIds))
                    ->getQuery()
                    ->execute();
            }
        }

        // do not load team members or leads by default, because they will only be used on detail pages
        // and there is no benefit in adding multiple queries for most requests when they are only needed in one place
        if ($this->hydrateTeamMembers) {
            $teamIds = [];
            foreach ($results as $project) {
                foreach ($project->getTeams() as $team) {
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
