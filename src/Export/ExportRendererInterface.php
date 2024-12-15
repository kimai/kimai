<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\HttpFoundation\Response;

interface ExportRendererInterface
{
    /**
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @return Response
     */
    public function render(array $exportItems, TimesheetQuery $query): Response;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getTitle(): string;
}
