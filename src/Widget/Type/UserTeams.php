<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;

class UserTeams extends SimpleWidget implements AuthorizedWidget, UserWidget
{
    public function __construct()
    {
        $this->setId('UserTeams');
        $this->setTitle('label.my_teams');
        $this->setOption('id', '');
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (empty($options['id'])) {
            $options['id'] = 'WidgetUserTeams';
        }

        return $options;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);
        /** @var User $user */
        $user = $options['user'];

        return $user->getTeams();
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_team_member', 'view_team'];
    }

    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
    }
}
