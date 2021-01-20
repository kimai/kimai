<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;

final class LockdownService
{
    private $configuration;
    private $isActive;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isLockdownActive(): bool
    {
        if ($this->isActive === null) {
            $this->isActive = $this->configuration->isTimesheetLockdownActive();
        }

        return $this->isActive;
    }

    /**
     * Does not check if the current user is allowed to edit timesheets in lockdown situations.
     * This needs to be performed earlier by yourself (see TimesheetVoter or LockdownValidator).
     *
     * @param Timesheet $timesheet
     * @param \DateTime $now
     * @param bool $allowEditInGracePeriod
     * @return bool
     */
    public function isEditable(Timesheet $timesheet, \DateTime $now, bool $allowEditInGracePeriod = false)
    {
        if (!$this->isLockdownActive()) {
            return true;
        }

        $timesheetStart = $timesheet->getBegin();

        if (null === $timesheetStart) {
            return true;
        }

        $lockedStart = $this->configuration->getTimesheetLockdownPeriodStart();
        $lockedEnd = $this->configuration->getTimesheetLockdownPeriodEnd();
        $gracePeriod = $this->configuration->getTimesheetLockdownGracePeriod();

        try {
            $lockdownStart = new \DateTime($lockedStart, $timesheetStart->getTimezone());
            $lockdownEnd = new \DateTime($lockedEnd, $timesheetStart->getTimezone());
            $lockdownGrace = clone $lockdownEnd;
            if (!empty($gracePeriod)) {
                $lockdownGrace->modify($gracePeriod);
            }
        } catch (\Exception $ex) {
            // should not happen, but ... if parsing of datetimes fails: skip validation
            return true;
        }

        // misconfiguration detected, skip validation
        if ($lockdownEnd < $lockdownStart) {
            return true;
        }

        // validate only entries added before the end of lockdown period
        if ($timesheetStart > $lockdownEnd) {
            return true;
        }

        // further validate entries inside of the most recent lockdown
        if ($timesheetStart > $lockdownStart && $timesheetStart < $lockdownEnd) {
            // if grace period is still in effect, validation succeeds
            if ($now < $lockdownGrace) {
                return true;
            }

            if ($allowEditInGracePeriod) {
                return true;
            }
        }

        return false;
    }
}
