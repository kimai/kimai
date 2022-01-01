<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\TimesheetTeamMultiUpdateSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\AbstractActionsSubscriber
 * @covers \App\EventSubscriber\Actions\TimesheetTeamMultiUpdateSubscriber
 * @covers \App\EventSubscriber\Actions\TimesheetTeamMultiUpdateSubscriber
 */
class TimesheetTeamMultiUpdateSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(TimesheetTeamMultiUpdateSubscriber::class, 'timesheets_team_multi_update');
    }
}
