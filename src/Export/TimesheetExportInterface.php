<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\HttpFoundation\Response;

interface TimesheetExportInterface
{
    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     */
    public function render(array $timesheets, TimesheetQuery $query): Response;

    /**
     * @return string
     */
    public function getId(): string;
}
