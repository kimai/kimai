<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Project\ProjectStatisticService;
use App\Repository\Loader\ProjectLoader;
use App\Repository\Loader\TeamLoader;
use Doctrine\ORM\EntityManagerInterface;

class UserTeamProjects extends SimpleWidget implements AuthorizedWidget, UserWidget
{
    private $statisticService;
    private $entityManager;

    public function __construct(ProjectStatisticService $statisticService, EntityManagerInterface $entityManager)
    {
        $this->setId('UserTeamProjects');
        $this->setTitle('label.my_team_projects');
        $this->setOption('id', '');
        $this->statisticService = $statisticService;
        $this->entityManager = $entityManager;
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (empty($options['id'])) {
            $options['id'] = 'WidgetUserTeamProjects';
        }

        return $options;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);
        /** @var User $user */
        $user = $options['user'];
        $now = new \DateTime('now', new \DateTimeZone($user->getTimezone()));

        $loader = new TeamLoader($this->entityManager);
        $loader->loadResults($user->getTeams());

        $teamProjects = [];
        $projects = [];

        /** @var Team $team */
        foreach ($user->getTeams() as $team) {
            /** @var Project $project */
            foreach ($team->getProjects() as $project) {
                if (!isset($teamProjects[$project->getId()])) {
                    $teamProjects[$project->getId()] = $project;
                }
            }
        }

        $loader = new ProjectLoader($this->entityManager);
        $loader->loadResults($teamProjects);

        foreach ($teamProjects as $id => $project) {
            if (!$project->isVisibleAtDate($now) || !$project->hasBudgets()) {
                continue;
            }
            $projects[$project->getId()] = $project;
        }

        return $this->statisticService->getBudgetStatisticModelForProjects($projects, $now);
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return [
            'budget_team_project', 'budget_teamlead_project', 'budget_project',
            'time_team_project', 'time_teamlead_project', 'time_project',
        ];
    }

    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
    }
}
