<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\Timesheet;
use App\Export\Base\RendererTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RendererTrait::class)]
class RendererTraitTest extends TestCase
{
    use TimesheetEntryFixtureTrait;

    private function getSut(): RendererTraitTestSubject
    {
        return new RendererTraitTestSubject();
    }

    public function testSummaryDurationDecimalReconcilesWithRoundedRowSum(): void
    {
        $summary = $this->getSut()->summary($this->createThreeTenMinuteEntries());
        $row = array_values($summary)[0];
        self::assertIsArray($row);

        // the raw sum is 1800 seconds -> round(1800/3600, 2) == 0.50, but the sum of the
        // three already-rounded 0.17 rows is 0.51 - that is what durationDecimal must hold
        self::assertSame(1800, $row['duration']);
        self::assertEqualsWithDelta(0.51, $row['durationDecimal'], 0.00001);

        self::assertIsArray($row['activities']);
        $activityRow = array_values($row['activities'])[0];
        self::assertIsArray($activityRow);
        self::assertEqualsWithDelta(0.51, $activityRow['durationDecimal'], 0.00001);
    }

    public function testSummaryRateDecimalReconcilesWithRoundedRowSum(): void
    {
        $summary = $this->getSut()->summary($this->createThreeTenMinuteEntries());
        $row = array_values($summary)[0];
        self::assertIsArray($row);

        // three rows of 1.6666667 print as 1.67 each; their reconciled sum is 5.01,
        // not round(3 * 1.6666667, 2) == 5.00
        self::assertEqualsWithDelta(5.01, $row['rateDecimal'], 0.00001);

        self::assertIsArray($row['activities']);
        $activityRow = array_values($row['activities'])[0];
        self::assertIsArray($activityRow);
        self::assertEqualsWithDelta(5.01, $activityRow['rateDecimal'], 0.00001);
    }
}

final class RendererTraitTestSubject
{
    use RendererTrait;

    /**
     * @param Timesheet[] $exportItems
     * @return array<mixed>
     */
    public function summary(array $exportItems): array
    {
        return $this->calculateSummary($exportItems);
    }
}
