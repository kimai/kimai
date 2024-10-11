<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectInactive;

use App\Entity\User;
use App\Reporting\ProjectInactive\ProjectInactiveQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectInactive\ProjectInactiveQuery
 */
class ProjectInactiveQueryTest extends TestCase
{
    public function testDefaults(): void
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectInactiveQuery($date, $user);

        self::assertEquals($date, $sut->getLastChange());
        self::assertSame($user, $sut->getUser());
    }

    public function testSetterGetter(): void
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectInactiveQuery($date, $user);

        $date1 = new \DateTime('2020-01-02 19:23:34');
        $sut->setLastChange($date1);

        self::assertEquals($date1, $sut->getLastChange());
    }
}
