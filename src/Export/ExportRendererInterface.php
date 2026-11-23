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

/**
 * FIXME change interface for 3.0
 * @method string getType()
 * @method bool isInternal()
 */
interface ExportRendererInterface
{
    /**
     * @param ExportableItem[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response;

    public function getId(): string;

    public function getTitle(): string;
}
