<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\ReportingEvent;
use App\Reporting\Report;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ReportingEvent
 */
class ReportingEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $user = new User();

        $sut = new ReportingEvent($user);

        self::assertSame($user, $sut->getUser());
        self::assertEquals([], $sut->getReports());

        $report = new Report('id', 'route', 'label', 'icon');
        $sut->addReport($report);
        self::assertSame([$report], $sut->getReports());
    }
}
