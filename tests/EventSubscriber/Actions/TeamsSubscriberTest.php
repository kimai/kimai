<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\TeamsSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TeamsSubscriber::class)]
class TeamsSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(TeamsSubscriber::class, 'teams');
    }
}
