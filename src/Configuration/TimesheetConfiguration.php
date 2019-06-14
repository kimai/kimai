<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class TimesheetConfiguration implements SystemBundleConfiguration
{
    /**
     * @deprecated
     */
    public const MODE_DURATION_FIXED_START = 'duration_fixed_start';
    /**
     * @deprecated
     */
    public const MODE_DURATION_ONLY = 'duration_only';
    /**
     * @deprecated
     */
    public const MODE_DEFAULT = 'default';
    /**
     * @deprecated
     */
    public const MODE_PUNCH_IN_OUT = 'punch';

    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'timesheet';
    }

    public function isAllowFutureTimes(): bool
    {
        return (bool) $this->find('rules.allow_future_times');
    }

    public function getTrackingMode(): string
    {
        return (string) $this->find('mode');
    }

    public function getDefaultBeginTime(): string
    {
        return (string) $this->find('default_begin');
    }

    public function isMarkdownEnabled(): bool
    {
        return (bool) $this->find('markdown_content');
    }

    public function getActiveEntriesHardLimit(): int
    {
        return (int) $this->find('active_entries.hard_limit');
    }

    public function getActiveEntriesSoftLimit(): int
    {
        return (int) $this->find('active_entries.soft_limit');
    }
}
