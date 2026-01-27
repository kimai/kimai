<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;

final class TimeSpanCalculator
{
    public const DEFAULT_GAP_TOLERANCE_MINUTES = 3;
    public const DEFAULT_MAX_DURATION_HOURS = 16;

    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly TimesheetRepository $timesheetRepository,
    ) {
    }

    /**
     * Calculate working time for each day of the year.
     *
     * @return array<string, int> Format: ['Y-m-d' => seconds, ...]
     */
    public function calculateForYear(User $user, \DateTimeInterface $year): array
    {
        $maxDuration = $this->getMaxDuration();
        $gapTolerance = $this->getGapTolerance();

        // Load timesheets with buffer for year boundaries
        $timesheets = $this->loadTimesheets($user, $year, $maxDuration);

        // Filter out individual tasks that exceed max duration
        $timesheets = $this->filterByMaxDuration($timesheets, $maxDuration);

        // Group by start day (local date)
        $groupedByDay = $this->groupByStartDay($timesheets);

        // Track all days that originally had entries (before reassignment)
        $daysWithEntries = array_keys($groupedByDay);

        // Reassign entries that overlap with overnight entries from previous day
        $groupedByDay = $this->reassignOverlappingEntries($groupedByDay, $gapTolerance);

        // Process each day: merge overlapping entries
        $results = [];
        $carryOver = []; // Overflow from previous days due to 16h split

        // Get year boundaries
        $yearStart = new \DateTimeImmutable($year->format('Y') . '-01-01');
        $yearEnd = new \DateTimeImmutable($year->format('Y') . '-12-31');

        // Process from buffer start to buffer end to handle carry-overs correctly
        $bufferStart = $yearStart->modify('-1 day');
        $bufferEnd = $yearEnd->modify('+1 day');

        $currentDay = $bufferStart;
        while ($currentDay <= $bufferEnd) {
            $dayKey = $currentDay->format('Y-m-d');
            $dayEntries = $groupedByDay[$dayKey] ?? [];

            // Add any carry-over from previous day's split
            if (isset($carryOver[$dayKey])) {
                $dayEntries = array_merge($carryOver[$dayKey], $dayEntries);
                unset($carryOver[$dayKey]);
            }

            if (!empty($dayEntries)) {
                // Merge overlapping/near entries into spans
                $spans = $this->mergeEntriesToSpans($dayEntries, $gapTolerance);

                // Check for 16h overnight split and process
                $processedSpans = $this->processOvernightSplits(
                    $spans,
                    $currentDay,
                    $maxDuration,
                    $carryOver
                );

                // Calculate total duration minus breaks
                $totalDuration = $this->calculateSpansDuration($processedSpans);

                $results[$dayKey] = $totalDuration;
            }

            $currentDay = $currentDay->modify('+1 day');
        }

        // Add 0 for days that originally had entries but now have none (due to reassignment)
        foreach ($daysWithEntries as $dayKey) {
            if (!isset($results[$dayKey])) {
                $results[$dayKey] = 0;
            }
        }

        // Trim buffer days from results (results are sorted chronologically)
        $yearStartStr = $yearStart->format('Y-m-d');
        $yearEndStr = $yearEnd->format('Y-m-d');

        // Trim from start: remove days before Jan 1
        while (!empty($results)) {
            $firstKey = array_key_first($results);
            if ($firstKey < $yearStartStr) {
                unset($results[$firstKey]);
            } else {
                break;
            }
        }

        // Trim from end: remove days after Dec 31
        while (!empty($results)) {
            $lastKey = array_key_last($results);
            if ($lastKey > $yearEndStr) {
                unset($results[$lastKey]);
            } else {
                break;
            }
        }

        return $results;
    }

    private function getGapTolerance(): int
    {
        $value = $this->systemConfiguration->find('working_time_calc.gap_tolerance');
        $minutes = $value !== null ? (int) $value : self::DEFAULT_GAP_TOLERANCE_MINUTES;

        return $minutes * 60;
    }

    private function getMaxDuration(): int
    {
        $value = $this->systemConfiguration->find('working_time_calc.max_duration');
        $hours = $value !== null ? (int) $value : self::DEFAULT_MAX_DURATION_HOURS;

        return $hours * 3600;
    }

    /**
     * Load timesheets for the year with buffer for boundary handling.
     *
     * @return Timesheet[]
     */
    private function loadTimesheets(User $user, \DateTimeInterface $year, int $maxDuration): array
    {
        // Buffer: max_duration before year start, max_duration after year end
        $begin = new \DateTimeImmutable($year->format('Y') . '-01-01 00:00:00');
        $begin = $begin->modify("-{$maxDuration} seconds");

        $end = new \DateTimeImmutable($year->format('Y') . '-12-31 23:59:59');
        $end = $end->modify("+{$maxDuration} seconds");

        $qb = $this->timesheetRepository->createQueryBuilder('t');

        $qb
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->gte('t.begin', ':begin'))
            ->andWhere($qb->expr()->lte('t.begin', ':end'))
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $user->getId())
            ->orderBy('t.begin', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Filter out timesheets that exceed maximum duration.
     *
     * @param Timesheet[] $timesheets
     * @return Timesheet[]
     */
    private function filterByMaxDuration(array $timesheets, int $maxDuration): array
    {
        return array_filter($timesheets, function (Timesheet $entry) use ($maxDuration) {
            $end = $entry->getEnd();
            $begin = $entry->getBegin();
            if ($end === null || $begin === null) {
                return false;
            }
            $duration = $end->getTimestamp() - $begin->getTimestamp();

            return $duration <= $maxDuration;
        });
    }

    /**
     * Group timesheets by their start day (local date).
     *
     * getBegin() returns a localized DateTime (calls localizeDates() internally),
     * so format('Y-m-d') gives the local date in the user's timezone.
     *
     * @param Timesheet[] $timesheets
     * @return array<string, Timesheet[]>
     */
    private function groupByStartDay(array $timesheets): array
    {
        $grouped = [];

        foreach ($timesheets as $entry) {
            $begin = $entry->getBegin();
            if ($begin === null) {
                continue;
            }
            // getBegin() returns localized DateTime, so this is the local date
            $dayKey = $begin->format('Y-m-d');
            $grouped[$dayKey][] = $entry;
        }

        return $grouped;
    }

    /**
     * Reassign entries that overlap with overnight entries from the previous day.
     *
     * Example: Entry A ends at 01:06 on day X+1 (started on day X).
     * Entry B starts at 00:45 on day X+1 and overlaps with A.
     * Entry B should be moved to day X to be merged with A.
     *
     * @param array<string, Timesheet[]> $groupedByDay
     * @param int $gapTolerance
     * @return array<string, Timesheet[]>
     */
    private function reassignOverlappingEntries(array $groupedByDay, int $gapTolerance): array
    {
        // Sort days chronologically
        ksort($groupedByDay);
        $days = array_keys($groupedByDay);

        $previousDayKey = null;
        $previousLatestEnd = null;

        foreach ($days as $dayKey) {
            // Check if we have an overnight overlap from previous day
            if ($previousDayKey !== null && $previousLatestEnd !== null) {
                // Verify this is actually the next calendar day
                $expectedNextDay = (new \DateTimeImmutable($previousDayKey))
                        ->modify('+1 day')
                        ->format('Y-m-d');

                if ($dayKey === $expectedNextDay) {
                    $midnight = new \DateTimeImmutable(
                        $dayKey . ' 00:00:00',
                        $previousLatestEnd->getTimezone()
                    );

                    // Check if previous day extends past midnight
                    if ($previousLatestEnd > $midnight) {
                        // Find and move overlapping entries
                        $entriesToMove = [];
                        $entriesToKeep = [];

                        foreach ($groupedByDay[$dayKey] as $entry) {
                            $entryBegin = $entry->getBegin();
                            if ($entryBegin === null) {
                                continue;
                            }
                            $gap = $entryBegin->getTimestamp() - $previousLatestEnd->getTimestamp();

                            if ($gap <= $gapTolerance) {
                                $entriesToMove[] = $entry;
                                // Update latestEnd if this entry extends further
                                $entryEnd = $entry->getEnd();
                                if ($entryEnd !== null && $entryEnd > $previousLatestEnd) {
                                    $previousLatestEnd = $entryEnd;
                                }
                            } else {
                                $entriesToKeep[] = $entry;
                            }
                        }

                        if (!empty($entriesToMove)) {
                            $groupedByDay[$previousDayKey] = array_merge(
                                $groupedByDay[$previousDayKey],
                                $entriesToMove
                            );

                            if (empty($entriesToKeep)) {
                                unset($groupedByDay[$dayKey]);
                                // Skip updating previous - continue with same overnight span
                                continue;
                            } else {
                                $groupedByDay[$dayKey] = $entriesToKeep;
                            }
                        }
                    }
                }
            }

            // Calculate latest end for current day
            $latestEnd = null;
            foreach ($groupedByDay[$dayKey] ?? [] as $entry) {
                if ($latestEnd === null || $entry->getEnd() > $latestEnd) {
                    $latestEnd = $entry->getEnd();
                }
            }

            $previousDayKey = $dayKey;
            $previousLatestEnd = $latestEnd;
        }

        return $groupedByDay;
    }

    /**
     * Merge overlapping or near entries into time spans.
     *
     * @param array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}> $entries
     * @return array<array{start: \DateTimeInterface, end: \DateTimeInterface, entries: array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>}>
     */
    private function mergeEntriesToSpans(array $entries, int $gapTolerance): array
    {
        if (empty($entries)) {
            return [];
        }

        // Sort by start time
        usort($entries, function ($a, $b) {
            $startA = $a instanceof Timesheet ? $a->getBegin() : $a['start'];
            $startB = $b instanceof Timesheet ? $b->getBegin() : $b['start'];

            return $startA <=> $startB;
        });

        $spans = [];
        $currentSpan = null;

        foreach ($entries as $entry) {
            $entryStart = $entry instanceof Timesheet ? $entry->getBegin() : $entry['start'];
            $entryEnd = $entry instanceof Timesheet ? $entry->getEnd() : $entry['end'];

            // Skip entries with null start/end
            if ($entryStart === null || $entryEnd === null) {
                continue;
            }

            if ($currentSpan === null) {
                $currentSpan = [
                    'start' => $entryStart,
                    'end' => $entryEnd,
                    'entries' => [$entry],
                ];
                continue;
            }

            // Check if entry overlaps or is near enough
            /** @var \DateTimeInterface $currentSpanEnd */
            $currentSpanEnd = $currentSpan['end'];
            $gap = $entryStart->getTimestamp() - $currentSpanEnd->getTimestamp();

            if ($gap <= $gapTolerance) {
                // Merge: extend span if needed
                if ($entryEnd > $currentSpanEnd) {
                    $currentSpan['end'] = $entryEnd;
                }
                $currentSpan['entries'][] = $entry;
            } else {
                // Start new span
                $spans[] = $currentSpan;
                $currentSpan = [
                    'start' => $entryStart,
                    'end' => $entryEnd,
                    'entries' => [$entry],
                ];
            }
        }

        if (!empty($currentSpan)) {
            $spans[] = $currentSpan;
        }

        return $spans;
    }

    /**
     * Process overnight splits for spans that extend >16h past midnight.
     *
     * @param array<array{start: \DateTimeInterface, end: \DateTimeInterface, entries: array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>}> $spans
     * @param array<string, array<array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>> $carryOver
     * @return array<array{start: \DateTimeInterface, end: \DateTimeInterface, entries: array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>}>
     */
    private function processOvernightSplits(
        array $spans,
        \DateTimeInterface $currentDay,
        int $maxDuration,
        array &$carryOver
    ): array {
        $processedSpans = [];
        $nextMidnight = new \DateTimeImmutable($currentDay->format('Y-m-d') . ' 00:00:00');
        $nextMidnight = $nextMidnight->modify('+1 day');

        foreach ($spans as $span) {
            $spanEnd = $span['end'];

            // Check if span extends past midnight
            if ($spanEnd <= $nextMidnight) {
                // Span ends before or at midnight - no overnight issue
                $processedSpans[] = $span;
                continue;
            }

            // Span extends into next day - calculate hours after midnight
            $hoursAfterMidnight = $spanEnd->getTimestamp() - $nextMidnight->getTimestamp();

            if ($hoursAfterMidnight <= $maxDuration) {
                // Less than 16h after midnight - no split needed
                $processedSpans[] = $span;
                continue;
            }

            // More than 16h after midnight - need to split
            // Find the split point: end of the last entry that ends before the 16h mark
            $splitPoint = $this->findSplitPoint($span['entries'], $nextMidnight, $maxDuration);

            if ($splitPoint !== null) {
                // Create span for current day (up to split point)
                $entriesBeforeSplit = $this->getEntriesEndingBefore($span['entries'], $splitPoint);
                $processedSpans[] = [
                    'start' => $span['start'],
                    'end' => $splitPoint,
                    'entries' => $entriesBeforeSplit,
                ];

                // Create carry-over for next day (from split point onwards)
                $entriesAfterSplit = $this->getEntriesEndingAfter($span['entries'], $splitPoint);
                if (!empty($entriesAfterSplit)) {
                    $nextDayKey = $nextMidnight->format('Y-m-d');
                    $carryOver[$nextDayKey][] = [
                        'virtual' => true,
                        'start' => $splitPoint,
                        'end' => $spanEnd,
                        'break' => $this->sumBreaksFromEntries($entriesAfterSplit),
                    ];
                }
            } else {
                // No valid split point found, include entire span
                $processedSpans[] = $span;
            }
        }

        return $processedSpans;
    }

    /**
     * Find the split point for overnight split.
     * Returns the end time of the last entry that ends at or before the 16h mark.
     *
     * @param array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}> $entries
     */
    private function findSplitPoint(array $entries, \DateTimeInterface $midnight, int $maxDuration): ?\DateTimeInterface
    {
        $maxTime = $midnight->getTimestamp() + $maxDuration;
        $lastValidEnd = null;

        foreach ($entries as $entry) {
            $entryEnd = $entry instanceof Timesheet ? $entry->getEnd() : $entry['end'];
            if ($entryEnd === null) {
                continue;
            }
            if ($entryEnd->getTimestamp() <= $maxTime) {
                if ($lastValidEnd === null || $entryEnd > $lastValidEnd) {
                    $lastValidEnd = $entryEnd;
                }
            }
        }

        return $lastValidEnd;
    }

    /**
     * Get entries that end at or before the given time.
     *
     * @param array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}> $entries
     * @return array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>
     */
    private function getEntriesEndingBefore(array $entries, \DateTimeInterface $time): array
    {
        return array_filter($entries, function ($entry) use ($time) {
            $end = $entry instanceof Timesheet ? $entry->getEnd() : $entry['end'];

            return $end <= $time;
        });
    }

    /**
     * Get entries that end after the given time.
     *
     * @param array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}> $entries
     * @return array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>
     */
    private function getEntriesEndingAfter(array $entries, \DateTimeInterface $time): array
    {
        return array_filter($entries, function ($entry) use ($time) {
            $end = $entry instanceof Timesheet ? $entry->getEnd() : $entry['end'];

            return $end > $time;
        });
    }

    /**
     * Sum breaks from entries (Timesheet or virtual).
     *
     * @param array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}> $entries
     */
    private function sumBreaksFromEntries(array $entries): int
    {
        $total = 0;
        foreach ($entries as $entry) {
            if ($entry instanceof Timesheet) {
                $total += $entry->getBreak();
            } elseif (\is_array($entry) && isset($entry['break'])) {
                $total += (int) $entry['break'];
            }
        }

        return $total;
    }

    /**
     * Calculate total duration of spans minus breaks.
     *
     * @param array<array{start: \DateTimeInterface, end: \DateTimeInterface, entries: array<Timesheet|array{virtual: bool, start: \DateTimeInterface, end: \DateTimeInterface, break: int}>}> $spans
     * @return int Duration in seconds (minimum 0)
     */
    private function calculateSpansDuration(array $spans): int
    {
        $totalDuration = 0;
        $totalBreaks = 0;

        foreach ($spans as $span) {
            $spanDuration = $span['end']->getTimestamp() - $span['start']->getTimestamp();
            $totalDuration += $spanDuration;

            // Sum breaks from all entries in this span
            if (isset($span['entries'])) {
                $totalBreaks += $this->sumBreaksFromEntries($span['entries']);
            }
        }

        return (int) max(0, $totalDuration - $totalBreaks);
    }
}
