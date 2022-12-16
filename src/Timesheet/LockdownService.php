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
use App\Entity\User;

final class LockdownService
{
    private ?bool $isActive = null;

    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function isLockdownActive(): bool
    {
        if ($this->isActive === null) {
            $this->isActive = $this->getLockdownPeriodStart() !== null && $this->getLockdownPeriodEnd() !== null;
        }

        return $this->isActive;
    }

    public function getLockdownTimezone(): ?string
    {
        $timezone = $this->configuration->find('timesheet.rules.lockdown_period_timezone');

        if ($timezone === null || $timezone === '') {
            return null;
        }

        return (string) $timezone;
    }

    private function getTimezone(User $user): \DateTimeZone
    {
        $timezone = $this->getLockdownTimezone();

        if ($timezone === null) {
            $timezone = $user->getTimezone();
        }

        return new \DateTimeZone($timezone);
    }

    public function getLockdownStart(User $user): ?\DateTimeInterface
    {
        $start = $this->getLockdownPeriodStart();
        if ($start === null) {
            return null;
        }

        $start = new \DateTimeImmutable($start, $this->getTimezone($user));

        return $start->setTimezone(new \DateTimeZone($user->getTimezone()));
    }

    private function getLockdownPeriodStart(): ?string
    {
        $start = $this->configuration->find('timesheet.rules.lockdown_period_start');

        if (!\is_string($start) || trim($start) === '') {
            return null;
        }

        $start = explode(',', $start);
        if (\count($start) === 1) {
            return $start[0];
        }

        $min = null;
        $date = null;
        foreach ($start as $dateString) {
            $tmp = new \DateTimeImmutable($dateString);
            if ($min === null) {
                $min = $dateString;
                $date = $tmp;
                continue;
            }
            if ($tmp > $date) {
                $min = $dateString;
                $date = $tmp;
            }
        }

        return $min;
    }

    public function getLockdownEnd(User $user): ?\DateTimeInterface
    {
        $end = $this->getLockdownPeriodEnd();
        if ($end === null) {
            return null;
        }

        $end = new \DateTimeImmutable($end, $this->getTimezone($user));

        return $end->setTimezone(new \DateTimeZone($user->getTimezone()));
    }

    private function getLockdownPeriodEnd(): ?string
    {
        $end = $this->configuration->find('timesheet.rules.lockdown_period_end');

        if (!\is_string($end) || trim($end) === '') {
            return null;
        }

        $end = explode(',', $end);
        if (\count($end) === 1) {
            return $end[0];
        }

        $min = null;
        $date = null;
        foreach ($end as $dateString) {
            $tmp = new \DateTimeImmutable($dateString);
            if ($min === null) {
                $min = $dateString;
                $date = $tmp;
                continue;
            }
            if ($tmp > $date) {
                $min = $dateString;
                $date = $tmp;
            }
        }

        return $min;
    }

    public function getLockdownGrace(User $user): ?\DateTimeInterface
    {
        $gracePeriod = $this->getLockdownGracePeriod();
        if ($gracePeriod === null) {
            return null;
        }

        $end = $this->getLockdownEnd($user);
        if ($end === null) {
            return null;
        }

        $grace = \DateTimeImmutable::createFromInterface($end);

        return $grace->modify($gracePeriod);
    }

    private function getLockdownGracePeriod(): ?string
    {
        $grace = $this->configuration->find('timesheet.rules.lockdown_grace_period');

        if ($grace === null || $grace === '') {
            return null;
        }

        return (string) $grace;
    }

    /**
     * Does not check if the current user is allowed to edit timesheets in lockdown situations.
     * This needs to be performed earlier by yourself (see TimesheetVoter or LockdownValidator).
     *
     * @param Timesheet $timesheet
     * @param \DateTimeInterface $now
     * @param bool $allowEditInGracePeriod
     * @return bool
     */
    public function isEditable(Timesheet $timesheet, \DateTimeInterface $now, bool $allowEditInGracePeriod = false): bool
    {
        if (!$this->isLockdownActive()) {
            return true;
        }

        $timesheetStart = $timesheet->getBegin();

        if (null === $timesheetStart) {
            return true;
        }

        $lockedStart = $this->getLockdownPeriodStart();
        $lockedEnd = $this->getLockdownPeriodEnd();

        if ($lockedStart === null || $lockedEnd === null) {
            return true;
        }

        $gracePeriod = $this->getLockdownGracePeriod();
        $timezone = $this->getLockdownTimezone();

        if ($timezone === null) {
            $timezone = $timesheetStart->getTimezone();
        } else {
            $timezone = new \DateTimeZone($timezone);
        }

        try {
            $lockdownStart = new \DateTimeImmutable($lockedStart, $timezone);
            $lockdownEnd = new \DateTimeImmutable($lockedEnd, $timezone);
            $lockdownGrace = clone $lockdownEnd;
            if (!empty($gracePeriod)) {
                $lockdownGrace = $lockdownGrace->modify($gracePeriod);
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
        if ($timesheetStart >= $lockdownStart) {
            // if grace period is still in effect, validation succeeds
            if ($now <= $lockdownGrace) {
                return true;
            }

            if ($allowEditInGracePeriod) {
                return true;
            }
        }

        return false;
    }
}
