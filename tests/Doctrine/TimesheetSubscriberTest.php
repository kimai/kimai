<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Doctrine;

use App\Doctrine\TimesheetSubscriber;
use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\TimesheetSubscriber
 */
class TimesheetSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $sut = new TimesheetSubscriber([]);
        $events = $sut->getSubscribedEvents();
        $this->assertTrue(\in_array(Events::onFlush, $events));
    }
}
