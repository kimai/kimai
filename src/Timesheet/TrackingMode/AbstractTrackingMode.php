<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Timesheet\UserDateTimeFactory;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTrackingMode implements TrackingModeInterface
{
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    public function __construct(UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
    }

    public function create(Timesheet $timesheet, Request $request): void
    {
        $this->setBeginEndFromRequest($timesheet, $request);
    }

    protected function setBeginEndFromRequest(Timesheet $entry, Request $request)
    {
        $start = $request->get('begin');
        if ($start !== null) {
            $start = $this->dateTime->createDateTimeFromFormat('Y-m-d', $start);
            if ($start !== false) {
                $entry->setBegin($start);

                // only check for an end date if a begin date was given
                $end = $request->get('end');
                if ($end !== null) {
                    $end = $this->dateTime->createDateTimeFromFormat('Y-m-d', $end);
                    if ($end !== false) {
                        $start->setTime(10, 0, 0);
                        $end->setTime(18, 0, 0);

                        $entry->setEnd($end);
                        $entry->setDuration($end->getTimestamp() - $start->getTimestamp());
                    }
                }
            }
        }

        $from = $request->get('from');
        if ($from !== null) {
            $from = $this->dateTime->createDateTime($from);
            if ($from !== false) {
                $entry->setBegin($from);

                // only check for an end datetime if a begin datetime was given
                $to = $request->get('to');
                if ($to !== null) {
                    $to = $this->dateTime->createDateTime($to);
                    if ($to !== false) {
                        $entry->setEnd($to);
                        $entry->setDuration($to->getTimestamp() - $from->getTimestamp());
                    }
                }
            }
        }
    }
}
