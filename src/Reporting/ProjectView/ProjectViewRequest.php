<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Entity\Project;
use App\Entity\User;
use DateTime;

final class ProjectViewRequest
{
    private $user;
    private $projects;
    private $dateTime;

    /**
     * @param User $user
     * @param Project[] $projects
     * @param DateTime|null $today
     */
    public function __construct(User $user, array $projects, ?\DateTime $today = null)
    {
        $this->user = $user;
        $this->projects = $projects;
        $this->dateTime = $today;
    }

    public function getToday(): ?DateTime
    {
        return $this->dateTime;
    }

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
