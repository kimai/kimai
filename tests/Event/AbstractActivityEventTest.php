<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Activity;
use App\Event\AbstractActivityEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractActivityEventTest extends TestCase
{
    abstract protected function createActivityEvent(Activity $activity): AbstractActivityEvent;

    public function testGetterAndSetter(): void
    {
        $activity = new Activity();
        $sut = $this->createActivityEvent($activity);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($activity, $sut->getActivity());
    }
}
