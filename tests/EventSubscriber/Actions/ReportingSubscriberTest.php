<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\ReportingSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\ReportingSubscriber
 */
class ReportingSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(ReportingSubscriber::class, 'reporting');
    }
}
