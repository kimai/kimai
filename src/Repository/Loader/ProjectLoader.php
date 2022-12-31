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

final class ProjectLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private bool $hydrateTeamMembers = false, private bool $hydrateTeams = true, private bool $hydrateMeta = true)
    {
    }

    /**
     * @param array<int|Project> $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $ids = array_map(function ($project) {
            if ($project instanceof Project) {
                return $project->getId();
            }

            return $project;
        }, $results);

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        /** @var Project[] $projects */
        $projects = $qb->select('PARTIAL project.{id}', 'customer')
            ->from(Project::class, 'project')
            ->leftJoin('project.customer', 'customer')
            ->andWhere($qb->expr()->in('project.id', $ids))
            ->getQuery()
            ->execute();

        $customerIds = array_unique(array_map(function (Project $project) {
            return $project->getCustomer()->getId();
        }, $projects));

        if ($this->hydrateMeta) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'meta')
                ->from(Project::class, 'project')
                ->leftJoin('project.meta', 'meta')
                ->andWhere($qb->expr()->in('project.id', $ids))
                ->getQuery()
                ->execute();
        }

        if ($this->hydrateTeams) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'teams')
                ->from(Project::class, 'project')
                ->leftJoin('project.teams', 'teams')
                ->andWhere($qb->expr()->in('project.id', $ids))
                ->getQuery()
                ->execute();

            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL customer.{id}', 'teams')
                ->from(Customer::class, 'customer')
                ->leftJoin('customer.teams', 'teams')
                ->andWhere($qb->expr()->in('customer.id', $customerIds))
                ->getQuery()
                ->execute();
        }

        // do not load team members or leads by default, because they will only be used on detail pages
        // and there is no benefit in adding multiple queries for most requests when they are only needed in one place
        if ($this->hydrateTeamMembers) {
            $teamIds = [];
            foreach ($projects as $project) {
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
