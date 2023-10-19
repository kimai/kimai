<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use App\Widget\WidgetInterface;

final class TotalsProject extends AbstractWidget
{
    public function __construct(private ProjectRepository $project)
    {
    }

    public function getTitle(): string
    {
        return 'stats.projectTotal';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_project',
            'icon' => 'project',
            'color' => WidgetInterface::COLOR_TOTAL,
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $user = $this->getUser();
        $query = new ProjectQuery();
        $query->setCurrentUser($user);

        return $this->project->countProjectsForQuery($query);
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_project', 'view_teamlead_project', 'view_team_project'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }

    public function getId(): string
    {
        return 'TotalsProject';
    }
}
