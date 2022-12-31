<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Helper;

use App\User\TeamService;
use App\User\UserService;
use Symfony\Component\Form\FormBuilderInterface;

final class ToolbarHelper
{
    /** @var array<string> */
    private array $teamNames = ['team', 'teams', 'searchTeams'];
    /** @var array<string> */
    private array $userNames = ['user', 'users'];

    public function __construct(private UserService $userService, private TeamService $teamService)
    {
    }

    public function cleanupForm(FormBuilderInterface $builder): void
    {
        $deleteUser = false;
        foreach ($this->userNames as $name) {
            if ($builder->has($name) && $this->userService->countUser(true) < 2) {
                $deleteUser = true;
                break;
            }
        }

        if ($deleteUser) {
            foreach ($this->userNames as $name) {
                if ($builder->has($name)) {
                    $builder->remove($name);
                }
            }
        }

        $deleteTeams = false;
        foreach ($this->teamNames as $name) {
            if ($builder->has($name) && !$this->teamService->hasTeams()) {
                $deleteTeams = true;
                break;
            }
        }

        if ($deleteTeams) {
            foreach ($this->teamNames as $name) {
                if ($builder->has($name)) {
                    $builder->remove($name);
                }
            }
        }
    }
}
