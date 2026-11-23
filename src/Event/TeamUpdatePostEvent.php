<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'team.updated', description: 'Triggered after a team was updated', payload: 'object.getTeam()')]
final class TeamUpdatePostEvent extends AbstractTeamEvent
{
}
