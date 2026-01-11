<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

enum DateRangeEnum: string
{
    case TODAY = 'today';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
    case TOTAL = 'total';
    case FINANCIAL = 'financial';

    public function getColorName(): string
    {
        return match ($this) {
            DateRangeEnum::TODAY => 'green',
            DateRangeEnum::WEEK => 'blue',
            DateRangeEnum::MONTH => 'purple',
            DateRangeEnum::FINANCIAL, DateRangeEnum::YEAR => 'yellow',
            DateRangeEnum::TOTAL => 'red',
        };
    }

    public function getTitle(): string
    {
        return match ($this) {
            DateRangeEnum::TODAY => 'daterangepicker.today',
            DateRangeEnum::WEEK => 'daterangepicker.thisWeek',
            DateRangeEnum::MONTH => 'daterangepicker.thisMonth',
            DateRangeEnum::YEAR => 'daterangepicker.thisYear',
            DateRangeEnum::FINANCIAL => 'daterangepicker.thisFinancialYear',
            DateRangeEnum::TOTAL => 'daterangepicker.allTime',
        };
    }
}
