<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use Symfony\Component\HttpFoundation\Response;

interface TimesheetExportRenderer
{
    /**
     * @param Export $export
     * @return Response
     */
    public function create(Export $export): Response;

    /**
     * @return string
     */
    public function getId(): string;
}
