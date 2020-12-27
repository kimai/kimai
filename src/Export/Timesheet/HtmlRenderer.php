<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Timesheet;

use App\Export\Base\HtmlRenderer as BaseHtmlRenderer;
use App\Export\TimesheetExportInterface;
use App\Export\TimesheetExportRenderer;

final class HtmlRenderer extends BaseHtmlRenderer implements TimesheetExportInterface, TimesheetExportRenderer
{
    protected function getTemplate(): string
    {
        return 'timesheet/export.html.twig';
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'print';
    }
}
