<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

class UserTeams extends AbstractWidget
{
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
        return 'label.my_teams';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-userteams.html.twig';
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_team_member', 'view_team'];
    }

    public function getId(): string
    {
        return 'UserTeams';
    }

    public function getData(array $options = [])
    {
        return $this->getUser()->getTeams();
    }
}
