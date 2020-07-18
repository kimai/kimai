<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\CellFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface CellFormatterInterface
{
    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value);
}
