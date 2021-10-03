<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;

final class TotalsProject extends SimpleWidget implements UserWidget, AuthorizedWidget
{
    use UserWidgetTrait;

    private $project;

    public function __construct(ProjectRepository $project)
    {
        $this->project = $project;
        $this->setTitle('stats.projectTotal');
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_project',
            'icon' => 'project',
            'color' => 'primary',
            'dataType' => 'int',
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        if (null === $user || !($user instanceof User)) {
            throw new \InvalidArgumentException('Widget option "user" must be an instance of ' . User::class);
        }

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
}
