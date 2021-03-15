<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\TimesheetMultiUpdateSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\TimesheetMultiUpdateSubscriber
 */
class TimesheetMultiUpdateSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(TimesheetMultiUpdateSubscriber::class, 'timesheets_multi_update');
    }
}
