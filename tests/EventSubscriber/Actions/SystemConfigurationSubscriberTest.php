<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\SystemConfigurationSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\SystemConfigurationSubscriber
 */
class SystemConfigurationSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(SystemConfigurationSubscriber::class, 'system_configuration');
    }
}
