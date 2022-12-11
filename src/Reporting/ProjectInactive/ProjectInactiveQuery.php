<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectInactive;

use App\Entity\User;
use DateTime;

final class ProjectInactiveQuery
{
    private DateTime $lastChange;
    private User $user;

    public function __construct(DateTime $lastChange, User $user)
    {
        $this->lastChange = clone $lastChange;
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getLastChange(): DateTime
    {
        return $this->lastChange;
    }

    public function setLastChange(DateTime $lastChange): void
    {
        $this->lastChange = clone $lastChange;
    }
}
