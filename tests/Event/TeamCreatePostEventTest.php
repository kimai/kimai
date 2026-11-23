<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Team;
use App\Event\AbstractTeamEvent;
use App\Event\TeamCreatePostEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractTeamEventTestCase::class)]
#[CoversClass(TeamCreatePostEvent::class)]
class TeamCreatePostEventTest extends AbstractTeamEventTestCase
{
    protected function createTeamEvent(Team $team): AbstractTeamEvent
    {
        return new TeamCreatePostEvent($team);
    }
}
