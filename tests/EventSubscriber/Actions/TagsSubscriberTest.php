<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use App\EventSubscriber\Actions\TagsSubscriber;

#[CoversClass(TagsSubscriber::class)]
class TagsSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(TagsSubscriber::class, 'tags');
    }
}
