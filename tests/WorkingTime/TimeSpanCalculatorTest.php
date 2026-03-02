<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\WorkingTime\TimeSpanCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeSpanCalculator::class)]
class TimeSpanCalculatorTest extends TestCase
{
    private function getSut(array $timesheets, array $config = []): TimeSpanCalculator
    {
        $configuration = SystemConfigurationFactory::createStub($config);
        $repository = $this->createRepositoryMock($timesheets);

        return new TimeSpanCalculator($configuration, $repository);
    }

    private function createRepositoryMock(array $timesheets): TimesheetRepository
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());

        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $query->method('getResult')->willReturn($timesheets);
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);

        return $repository;
    }

    private function createUser(string $timezone = 'Europe/Berlin'): User
    {
        $user = new User();
        $user->setUserIdentifier('test_user_span');
        $user->setTimezone($timezone);

        return $user;
    }

    private function createTimesheet(User $user, string $begin, string $end, int $break = 0): Timesheet
    {
        $timezone = new \DateTimeZone($user->getTimezone());

        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setBegin(new \DateTime($begin, $timezone));
        $timesheet->setEnd(new \DateTime($end, $timezone));
        $timesheet->setBreak($break);

        return $timesheet;
    }

    // ========== Basis-Tests ==========

    public function testEmptyResult(): void
    {
        $user = $this->createUser();
        $sut = $this->getSut([]);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        self::assertEmpty($result);
    }

    public function testDefaultGapTolerance(): void
    {
        $user = $this->createUser();

        // Two entries with 3 min gap (= default tolerance) should merge
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 11:00:00'),
            $this->createTimesheet($user, '2026-01-15 11:03:00', '2026-01-15 12:00:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // Should merge into 1 span: 10:00-12:00 = 120 min = 7200 sec
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(7200, $result['2026-01-15']);
    }

    public function testDefaultMaxDuration(): void
    {
        $user = $this->createUser();

        // Entry longer than 16h should be filtered out
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 08:00:00', '2026-01-16 02:00:00'), // 18h - too long
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 11:00:00'), // 1h - ok
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // Only the 1h entry should remain
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(3600, $result['2026-01-15']);
    }

    public function testConfiguredGapTolerance(): void
    {
        $user = $this->createUser();

        // Two entries with 5 min gap - should merge with 5 min tolerance, not with default 3 min
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 11:00:00'),
            $this->createTimesheet($user, '2026-01-15 11:05:00', '2026-01-15 12:00:00'),
        ];

        // With default (3 min) they would stay separate
        $sutDefault = $this->getSut($timesheets);
        $resultDefault = $sutDefault->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));
        self::assertEquals(6900, $resultDefault['2026-01-15']); // 60 + 55 = 115 min = 6900 sec

        // With configured 5 min tolerance they should merge
        $sutConfigured = $this->getSut($timesheets, ['working_time_calc' => ['gap_tolerance' => 5]]);
        $resultConfigured = $sutConfigured->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));
        self::assertEquals(7200, $resultConfigured['2026-01-15']); // 10:00-12:00 = 120 min = 7200 sec
    }

    public function testConfiguredMaxDuration(): void
    {
        $user = $this->createUser();

        // Entry of 14h - should be filtered with 12h max, but kept with default 16h
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 08:00:00', '2026-01-15 22:00:00'), // 14h
        ];

        // With default (16h) it should be kept
        $sutDefault = $this->getSut($timesheets);
        $resultDefault = $sutDefault->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));
        self::assertEquals(50400, $resultDefault['2026-01-15']); // 14h = 50400 sec

        // With configured 12h max it should be filtered out
        $sutConfigured = $this->getSut($timesheets, ['working_time_calc' => ['max_duration' => 12]]);
        $resultConfigured = $sutConfigured->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));
        self::assertEmpty($resultConfigured);
    }

    // ========== Break-Handling ==========

    public function testBreakSubtractedFromDuration(): void
    {
        $user = $this->createUser();

        // Entry from 10:00-12:00 (2h) with 30 min break = 1.5h effective
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 12:00:00', 1800), // 30 min break
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // 2h span - 30 min break = 1.5h = 5400 sec
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(5400, $result['2026-01-15']);
    }

    public function testBreakSubtractedFromMergedSpan(): void
    {
        $user = $this->createUser();

        // Two overlapping entries, each with breaks
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 12:00:00', 900),  // 15 min break
            $this->createTimesheet($user, '2026-01-15 11:00:00', '2026-01-15 13:00:00', 900),  // 15 min break
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // Merged span: 10:00-13:00 = 3h = 10800 sec
        // Total breaks: 15 + 15 = 30 min = 1800 sec
        // Effective: 10800 - 1800 = 9000 sec
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(9000, $result['2026-01-15']);
    }

    // ========== Szenario 1: Gap Tolerance ==========

    public function testGapWithinTolerance(): void
    {
        $user = $this->createUser();

        // Two entries with exactly 3 min gap (= tolerance) should merge
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 11:00:00'),
            $this->createTimesheet($user, '2026-01-15 11:03:00', '2026-01-15 12:00:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // Merged: 10:00-12:00 = 120 min = 7200 sec
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(7200, $result['2026-01-15']);
    }

    public function testGapExceedsTolerance(): void
    {
        $user = $this->createUser();

        // Two entries with 4 min gap (> tolerance) should stay separate
        $timesheets = [
            $this->createTimesheet($user, '2026-01-15 10:00:00', '2026-01-15 11:00:00'),
            $this->createTimesheet($user, '2026-01-15 11:04:00', '2026-01-15 12:00:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // 2 separate spans: 60 min + 56 min = 116 min = 6960 sec
        self::assertArrayHasKey('2026-01-15', $result);
        self::assertEquals(6960, $result['2026-01-15']);
    }

    // ========== Szenario 2: Overlapping Entries Same Day ==========

    public function testOverlappingEntriesSameDay(): void
    {
        $user = $this->createUser();

        // Scenario from simpleWorkContract DB (local times Europe/Berlin):
        // Entry 4: 10:35-12:07 (isolated)
        // Entry 1: 15:34-16:19
        // Entry 2: 15:35-17:03 (overlaps with Entry 1)
        $timesheets = [
            $this->createTimesheet($user, '2026-01-22 10:35:00', '2026-01-22 12:07:00'),
            $this->createTimesheet($user, '2026-01-22 15:34:00', '2026-01-22 16:19:00'),
            $this->createTimesheet($user, '2026-01-22 15:35:00', '2026-01-22 17:03:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // Span1: 10:35-12:07 = 92 min
        // Span2: 15:34-17:03 = 89 min (merged)
        // Total: 181 min = 10860 sec
        self::assertArrayHasKey('2026-01-22', $result);
        self::assertEquals(10860, $result['2026-01-22']);
    }

    // ========== Szenario 3: Overnight with Reassignment + 16h Split ==========

    public function testOvernightWithReassignmentAndSplit(): void
    {
        $user = $this->createUser();

        // Scenario from simpleWorkContract DB (local times Europe/Berlin):
        // Entry 7: 06.01. 22:30 - 23:45
        // Entry 8: 06.01. 23:30 - 07.01. 15:15 (overnight, overlaps with 7)
        // Entry 9: 07.01. 15:00 - 16:15 (overlaps with end of 8, gets reassigned to 06.01)
        // Entry 10: 07.01. 19:00 - 19:14 (isolated)
        $timesheets = [
            $this->createTimesheet($user, '2026-01-06 22:30:00', '2026-01-06 23:45:00'),
            $this->createTimesheet($user, '2026-01-06 23:30:00', '2026-01-07 15:15:00'),
            $this->createTimesheet($user, '2026-01-07 15:00:00', '2026-01-07 16:15:00'),
            $this->createTimesheet($user, '2026-01-07 19:00:00', '2026-01-07 19:14:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // 06.01: Span 22:30 -> 15:15 (split at 15:15 because 16:15 > 16:00)
        // = 16h 45min = 60300 sec
        self::assertArrayHasKey('2026-01-06', $result);
        self::assertEquals(60300, $result['2026-01-06']);

        // 07.01: Carry-over (15:15-16:15 = 60min) + Entry 10 (19:00-19:14 = 14min)
        // = 74 min = 4440 sec
        self::assertArrayHasKey('2026-01-07', $result);
        self::assertEquals(4440, $result['2026-01-07']);
    }

    // ========== Szenario 4: Two Overnight Entries Overlapping ==========

    public function testTwoOvernightEntriesOverlapping(): void
    {
        $user = $this->createUser();

        // Scenario from simpleWorkContract DB (local times Europe/Berlin):
        // Entry 5: 13.01. 15:35 -> 14.01. 01:06 (overnight)
        // Entry 6: 14.01. 00:45 -> 14.01. 01:16 (starts after midnight, overlaps with 5, gets reassigned)
        $timesheets = [
            $this->createTimesheet($user, '2026-01-13 15:35:00', '2026-01-14 01:06:00'),
            $this->createTimesheet($user, '2026-01-14 00:45:00', '2026-01-14 01:16:00'),
        ];
        $sut = $this->getSut($timesheets);

        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // 13.01: Merged span 15:35 -> 01:16 = 9h 41min = 34860 sec
        self::assertArrayHasKey('2026-01-13', $result);
        self::assertEquals(34860, $result['2026-01-13']);

        // 14.01: 0 sec (Entry 6 reassigned to 13.01)
        if (isset($result['2026-01-14'])) {
            self::assertEquals(0, $result['2026-01-14']);
        }
    }

    // ========== Szenario 5: Year Boundary ==========

    public function testYearBoundary(): void
    {
        $user = $this->createUser();

        // Scenario from simpleWorkContract DB (local times Europe/Berlin):
        // Entry 11: 31.12. 14:25 - 14:30 (5 min)
        // Entry 13: 31.12. 23:45 -> 01.01. 01:15 (overnight over year boundary, 90 min)
        // Entry 3: 01.01. 14:25 - 14:30 (5 min)
        $timesheets = [
            $this->createTimesheet($user, '2025-12-31 14:25:00', '2025-12-31 14:30:00'),
            $this->createTimesheet($user, '2025-12-31 23:45:00', '2026-01-01 01:15:00'),
            $this->createTimesheet($user, '2026-01-01 14:25:00', '2026-01-01 14:30:00'),
        ];
        $sut = $this->getSut($timesheets);

        // Test for year 2026
        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2026-01-01'));

        // 01.01.2026: 1 span (14:25-14:30) = 5 min = 300 sec
        self::assertArrayHasKey('2026-01-01', $result);
        self::assertEquals(300, $result['2026-01-01']);

        // 31.12.2025 should not be in 2026 results (trimmed by year boundary)
        self::assertArrayNotHasKey('2025-12-31', $result);
    }

    public function testYearBoundaryPreviousYear(): void
    {
        $user = $this->createUser();

        // Same data but testing for year 2025
        $timesheets = [
            $this->createTimesheet($user, '2025-12-31 14:25:00', '2025-12-31 14:30:00'),
            $this->createTimesheet($user, '2025-12-31 23:45:00', '2026-01-01 01:15:00'),
            $this->createTimesheet($user, '2026-01-01 14:25:00', '2026-01-01 14:30:00'),
        ];
        $sut = $this->getSut($timesheets);

        // Test for year 2025
        $result = $sut->calculateForYear($user, new \DateTimeImmutable('2025-01-01'));

        // 31.12.2025: 2 spans (5 min + 90 min) = 95 min = 5700 sec
        self::assertArrayHasKey('2025-12-31', $result);
        self::assertEquals(5700, $result['2025-12-31']);

        // 01.01.2026 should not be in 2025 results
        self::assertArrayNotHasKey('2026-01-01', $result);
    }
}
