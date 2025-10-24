<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Model;

class RecordMergeMode
{
    public const MODE_NONE = 'NONE';
    public const MODE_MERGE = 'MERGE';
    public const MODE_MERGE_USE_FIRST_OF_DAY = 'MERGE_USE_FIRST_OF_DAY';
    public const MODE_MERGE_USE_LAST_OF_DAY = 'MERGE_USE_LAST_OF_DAY';

    /**
     * @return array<string, string>
     */
    public static function getModes(): array
    {
        return [
            self::MODE_NONE => 'shared_project_timesheets.model.merge_record_mode.none',
            self::MODE_MERGE => 'shared_project_timesheets.model.merge_record_mode.merge',
            self::MODE_MERGE_USE_FIRST_OF_DAY => 'shared_project_timesheets.model.merge_record_mode.merge_use_first_of_day',
            self::MODE_MERGE_USE_LAST_OF_DAY => 'shared_project_timesheets.model.merge_record_mode.merge_use_last_of_day',
        ];
    }
}
