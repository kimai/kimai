<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDetails;

use App\Entity\Project;
use App\Entity\User;
use DateTime;

final class ProjectDetailsQuery
{
    /**
     * @var Project|null
     */
    private $project;
    /**
     * @var DateTime
     */
    private $today;
    /**
     * @var User
     */
    private $user;

    public function __construct(DateTime $today, User $user)
    {
        $this->today = $today;
        $this->user = $user;
    }

    public function getToday(): DateTime
    {
        return $this->today;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }
}
