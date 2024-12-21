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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Response;

#[AutoconfigureTag]
interface TimesheetExportInterface
{
    /**
     * @param Timesheet[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response;

    public function getId(): string;

    public function getTitle(): string;
}
