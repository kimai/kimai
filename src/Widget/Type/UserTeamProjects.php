<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Project\ProjectStatisticService;
use App\Repository\Loader\TeamLoader;
use App\Widget\WidgetInterface;
use Doctrine\ORM\EntityManagerInterface;

final class UserTeamProjects extends AbstractWidget
{
    public function __construct(
        private readonly ProjectStatisticService $statisticService,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_HALF;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_LARGE;
    }

    public function getTitle(): string
    {
        return 'my_team_projects';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-userteamprojects.html.twig';
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

    public function getId(): string
    {
        return 'UserTeamProjects';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $user = $this->getUser();
        $teams = $user->getTeams();
        $now = new \DateTime('now', new \DateTimeZone($user->getTimezone()));

        $loader = new TeamLoader($this->entityManager, true);
        $loader->loadResults($teams);

        $projects = [];

        foreach ($teams as $team) {
            foreach ($team->getProjects() as $project) {
                if (!$project->isVisibleAtDate($now) || !$project->hasBudgets()) {
                    continue;
                }
                $projects[$project->getId()] = $project;
            }
        }

        return $this->statisticService->getBudgetStatisticModelForProjects($projects, $now);
    }
}
