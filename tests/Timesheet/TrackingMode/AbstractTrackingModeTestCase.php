<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Timesheet\TrackingMode\AbstractTrackingMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Timesheet\TrackingMode\AbstractTrackingMode
 */
abstract class AbstractTrackingModeTestCase extends TestCase
{
    /**
     * @return AbstractTrackingMode
     */
    abstract protected function createSut(string $default = '13:47', bool $allowApiTimes = false);

    protected function createTimesheet(): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setUser(new User());

        return $timesheet;
    }

    public function assertDefaultBegin(Timesheet $timesheet): void
    {
        self::assertNull($timesheet->getBegin());
    }

    public function testCreateDoesNotChangeAnythingOnEmptyRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();

        self::assertNull($timesheet->getBegin());
        self::assertNull($timesheet->getEnd());

        $sut->create($timesheet, new Request());

        $this->assertDefaultBegin($timesheet);
        self::assertNull($timesheet->getEnd());
    }

    public function testCreateUseBeginWithoutEndDateFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'begin' => '2017-07-23',
        ]);

        $sut->create($timesheet, $request);

        self::assertEquals('2017-07-23', $timesheet->getBegin()->format('Y-m-d'));
        self::assertNotEquals('10:00:00', $timesheet->getBegin()->format('H:i:s'));
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }

    public function testCreateUseBeginEndDateFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'begin' => '2017-07-23',
            'end' => '2017-07-23',
        ]);

        $sut->create($timesheet, $request);

        self::assertNotNull($timesheet->getBegin());
        self::assertNotNull($timesheet->getEnd());

        self::assertEquals('2017-07-23 10:00:00', $timesheet->getBegin()->format('Y-m-d H:i:s'));
        self::assertEquals('2017-07-23 18:00:00', $timesheet->getEnd()->format('Y-m-d H:i:s'));
        self::assertEquals(28800, $timesheet->getDuration());
    }

    public function testCreateIgnoresValidEndOnInvalidBeginDateFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'begin' => '10x0-99-99',
            'end' => '2017-07-23',
        ]);

        $sut->create($timesheet, $request);

        $this->assertDefaultBegin($timesheet);
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }

    public function testCreateUsesBeginAndIgnoresInvalidEndDateFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'begin' => '2017-07-23',
            'end' => '20xx-07-23',
        ]);

        $sut->create($timesheet, $request);

        self::assertEquals('2017-07-23', $timesheet->getBegin()->format('Y-m-d'));
        self::assertNotEquals('10:00:00', $timesheet->getBegin()->format('H:i:s'));
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }

    public function testCreateUseFromWithoutToDatetimeFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'from' => '2018-05-23 21:47:55',
        ]);

        $sut->create($timesheet, $request);

        self::assertEquals('2018-05-23 21:47:55', $timesheet->getBegin()->format('Y-m-d H:i:s'));
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }

    public function testCreateUseFromToDatetimeFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'from' => '2018-05-23 21:47:55',
            'to' => '2018-05-24 01:11:11',
        ]);

        $sut->create($timesheet, $request);

        self::assertNotNull($timesheet->getBegin());
        self::assertNotNull($timesheet->getEnd());

        self::assertEquals('2018-05-23 21:47:55', $timesheet->getBegin()->format('Y-m-d H:i:s'));
        self::assertEquals('2018-05-24 01:11:11', $timesheet->getEnd()->format('Y-m-d H:i:s'));
        self::assertEquals(12196, $timesheet->getDuration());
    }

    public function testCreateUseFromToDatetimeOverwritesBeginEndTatesFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'begin' => '2017-07-23',
            'end' => '2017-07-23',
            'from' => '2018-05-23 21:47:55',
            'to' => '2018-05-24 01:11:11',
        ]);

        $sut->create($timesheet, $request);

        self::assertNotNull($timesheet->getBegin());
        self::assertNotNull($timesheet->getEnd());

        self::assertEquals('2018-05-23 21:47:55', $timesheet->getBegin()->format('Y-m-d H:i:s'));
        self::assertEquals('2018-05-24 01:11:11', $timesheet->getEnd()->format('Y-m-d H:i:s'));
        self::assertEquals(12196, $timesheet->getDuration());
    }

    public function testCreateIgnoresValidToOnInvalidFromDatetimeFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'from' => '2018-xx-23 21:47:55',
            'to' => '2018-05-24 01:11:11',
        ]);

        $sut->create($timesheet, $request);

        $this->assertDefaultBegin($timesheet);
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }

    public function testCreateUsesFromAndIgnoresInvalidToDatetimeFromRequest(): void
    {
        $sut = $this->createSut();

        $timesheet = $this->createTimesheet();
        $request = new Request([
            'from' => '2018-05-23 21:47:55',
            'to' => '2018-xx-24 01:11:11',
        ]);

        $sut->create($timesheet, $request);

        self::assertEquals('2018-05-23 21:47:55', $timesheet->getBegin()->format('Y-m-d H:i:s'));
        self::assertNull($timesheet->getEnd());
        self::assertEquals(0, $timesheet->getDuration());
    }
}
