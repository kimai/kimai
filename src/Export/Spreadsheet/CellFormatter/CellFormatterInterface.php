<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\CellFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface CellFormatterInterface
{
    /**
     * @param Worksheet $sheet
     * @param int $column
     * @param int $row
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void;
}
