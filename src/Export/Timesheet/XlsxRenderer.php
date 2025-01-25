<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Timesheet;

use App\Export\Base\XlsxRenderer as BaseXlsxRenderer;
use App\Export\TimesheetExportInterface;

final class XlsxRenderer extends BaseXlsxRenderer implements TimesheetExportInterface
{
    public function getTitle(): string
    {
        return 'xlsx';
    }
}
