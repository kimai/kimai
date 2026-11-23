<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Team;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base event class triggered for Team manipulations.
 */
abstract class AbstractTeamEvent extends Event
{
    public function __construct(private readonly Team $team)
    {
    }

    public function getTeam(): Team
    {
        return $this->team;
    }
}
