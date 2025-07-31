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

    protected function setBeginEndFromRequest(Timesheet $entry, Request $request): void
    {
        $start = $request->query->get('begin');
        if (null === $start) {
            return;
        }

        $start = DateTime::createFromFormat('Y-m-d', $start, $this->getTimezone($entry));
        if (false === $start) {
            return;
        }

        $entry->setBegin($start);

        // only check for an end date if a begin date was given
        $end = $request->query->get('end');
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

    protected function setFromToFromRequest(Timesheet $entry, Request $request): void
    {
        $from = $request->query->get('from');
        if (!\is_string($from)) {
            return;
        }

        try {
            $from = new DateTime($from, $this->getTimezone($entry));
            $entry->setBegin($from);
        } catch (\Exception $ex) {
            return;
        }

        // only check for an end date if a valid begin date was given
        $to = $request->query->get('to');
        if (!\is_string($to)) {
            return;
        }

        try {
            $to = new DateTime($to, $this->getTimezone($entry));
            $entry->setEnd($to);
        } catch (\Exception $ex) {
            return;
        }

        $entry->setDuration($to->getTimestamp() - $from->getTimestamp());
    }
}
