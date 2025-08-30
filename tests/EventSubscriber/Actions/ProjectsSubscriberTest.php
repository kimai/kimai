<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\ProjectsSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectsSubscriber::class)]
class ProjectsSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(ProjectsSubscriber::class, 'projects');
    }
}
