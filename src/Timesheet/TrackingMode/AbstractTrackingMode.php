<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTrackingMode implements TrackingModeInterface
{
    use TrackingModeTrait;

    public function create(Timesheet $timesheet, ?Request $request = null): void
    {
        if (null === $request) {
            return;
        }

        $this->setBeginEndFromRequest($timesheet, $request);
        $this->setFromToFromRequest($timesheet, $request);
    }

    protected function setBeginEndFromRequest(Timesheet $entry, Request $request)
    {
        $start = $request->get('begin');
        if (null === $start) {
            return;
        }

        $start = DateTime::createFromFormat('Y-m-d', $start, $this->getTimezone($entry));
        if (false === $start) {
            return;
        }

        $entry->setBegin($start);

        // only check for an end date if a begin date was given
        $end = $request->get('end');
        if (null === $end) {
            return;
        }

        $end = DateTime::createFromFormat('Y-m-d', $end, $this->getTimezone($entry));
        if (false === $end) {
            return;
        }

        $start->setTime(10, 0, 0);
        $end->setTime(18, 0, 0);

        $entry->setEnd($end);
        $entry->setDuration($end->getTimestamp() - $start->getTimestamp());
    }

    protected function setFromToFromRequest(Timesheet $entry, Request $request)
    {
        $from = $request->get('from');
        if (null === $from) {
            return;
        }

        try {
            $from = new DateTime($from, $this->getTimezone($entry));
        } catch (\Exception $ex) {
            return;
        }

        $entry->setBegin($from);

        $to = $request->get('to');
        if (null === $to) {
            return;
        }

        try {
            $to = new DateTime($to, $this->getTimezone($entry));
        } catch (\Exception $ex) {
            return;
        }

        $entry->setEnd($to);
        $entry->setDuration($to->getTimestamp() - $from->getTimestamp());
    }
}
