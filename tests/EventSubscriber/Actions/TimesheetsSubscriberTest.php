<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\TimesheetsSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\AbstractActionsSubscriber
 * @covers \App\EventSubscriber\Actions\TimesheetsSubscriber
 */
class TimesheetsSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(TimesheetsSubscriber::class, 'timesheets');
    }
}
