<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\CellFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrayFormatter implements CellFormatterInterface
{
    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void
    {
        if (null === $value) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        if (!\is_array($value)) {
            throw new \InvalidArgumentException('Unsupported value given, only array is supported');
        }

        $sheet->setCellValueByColumnAndRow($column, $row, implode(';', $value));
    }
}
