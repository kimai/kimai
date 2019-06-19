<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Activity;
use App\Event\ActivityMetaDefinitionEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ActivityMetaDefinitionEvent
 */
class ActivityMetaDefinitionEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $activity = new Activity();
        $sut = new ActivityMetaDefinitionEvent($activity);
        $this->assertSame($activity, $sut->getEntity());
    }
}
