<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\CustomerSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomerSubscriber::class)]
class CustomerSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(CustomerSubscriber::class, 'customer');
    }
}
