<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Security\CurrentUser;

class UserTeams extends SimpleWidget
{
    public function __construct(CurrentUser $user)
    {
        $this->setId('UserTeams');
        $this->setTitle('label.teams');
        $this->setOptions([
            'user' => $user->getUser(),
            'id' => '',
        ]);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (empty($options['id'])) {
            $options['id'] = uniqid('UserTeams_');
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
        return ['view_team_member'];
    }
}
