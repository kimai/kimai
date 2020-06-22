<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Timesheet;

use App\Export\Base\PDFRenderer as BasePDFRenderer;
use App\Export\TimesheetExportInterface;

final class PDFRenderer extends BasePDFRenderer implements TimesheetExportInterface
{
}
