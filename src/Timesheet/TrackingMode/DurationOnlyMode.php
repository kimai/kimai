<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

final class DurationOnlyMode extends AbstractTrackingMode
{
    /**
     * @var SystemConfiguration
     */
    private $configuration;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function canEditBegin(): bool
    {
        return true;
    }

    public function canEditEnd(): bool
    {
        return false;
    }

    public function canEditDuration(): bool
    {
        return true;
    }

    public function canUpdateTimesWithAPI(): bool
    {
        return true;
    }

    public function getId(): string
    {
        return 'duration_only';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return false;
    }

    public function create(Timesheet $timesheet, ?Request $request = null): void
    {
        if (null === $timesheet->getBegin()) {
            $timesheet->setBegin(new DateTime('now', $this->getTimezone($timesheet)));
        }

        $newBegin = clone $timesheet->getBegin();
        $newBegin->modify($this->configuration->getTimesheetDefaultBeginTime());
        $timesheet->setBegin($newBegin);

        parent::create($timesheet, $request);
    }
}
