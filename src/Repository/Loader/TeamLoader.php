<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Project;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 * @implements LoaderInterface<Team>
 */
final class TeamLoader implements LoaderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $loadCustomer = false
    )
    {
    }

    /**
     * @param array<Team> $results
     */
    public function loadResults(array $results): void
    {
        if (\count($results) === 0) {
            return;
        }

        $teamIds = array_filter(array_unique(array_map(function (Team $team) {
            // make sure that this potential doctrine proxy is initialized and filled with all data
            $team->getName();

            return $team->getId();
        }, $results)), function ($value) { return $value !== null; });

        $em = $this->entityManager;

        // required wherever users are shown, e.g. on "Custom details" page
        if (\count($teamIds) > 0) {
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL team.{id}', 'members', 'user')
                ->from(Team::class, 'team')
                ->leftJoin('team.members', 'members')
                ->leftJoin('members.user', 'user')
                ->andWhere($qb->expr()->in('team.id', $teamIds))
                ->getQuery()
                ->execute();

            // used in UserTeamProjects widget
            $qb = $em->createQueryBuilder();
            /** @var array<Team> $teams */
            $teams = $qb->select('PARTIAL team.{id}', 'projects')
                ->from(Team::class, 'team')
                ->leftJoin('team.projects', 'projects')
                ->andWhere($qb->expr()->in('team.id', $teamIds))
                ->getQuery()
                ->execute();
        }

        $projectIds = [];
        foreach ($results as $team) {
            foreach ($team->getProjects() as $project) {
                $projectIds[] = $project->getId();
            }
        }

        if ($this->loadCustomer && \count($projectIds) > 0) {
            // used in UserTeamProjects widget
            $qb = $em->createQueryBuilder();
            $qb->select('PARTIAL project.{id}', 'customer')
                ->from(Project::class, 'project')
                ->leftJoin('project.customer', 'customer')
                ->andWhere($qb->expr()->in('project.id', $projectIds))
                ->getQuery()
                ->execute();
        }
    }
}
