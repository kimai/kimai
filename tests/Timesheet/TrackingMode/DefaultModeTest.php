<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use App\Tests\Mocks\RoundingServiceFactory;
use App\Timesheet\TrackingMode\DefaultMode;

/**
 * @covers \App\Timesheet\TrackingMode\DefaultMode
 */
class DefaultModeTest extends AbstractTrackingModeTestCase
{
    public function assertDefaultBegin(Timesheet $timesheet): void
    {
        self::assertNotNull($timesheet->getBegin());
    }

    /**
     * @return DefaultMode
     */
    protected function createSut(string $default = '13:47', bool $allowApiTimes = false): DefaultMode
    {
        return new DefaultMode((new RoundingServiceFactory($this))->create());
    }

    public function testDefaultValues(): void
    {
        $sut = $this->createSut();

        self::assertTrue($sut->canEditBegin());
        self::assertTrue($sut->canEditEnd());
        self::assertTrue($sut->canEditDuration());
        self::assertTrue($sut->canUpdateTimesWithAPI());
        self::assertTrue($sut->canSeeBeginAndEndTimes());
        self::assertEquals('default', $sut->getId());
    }
}
