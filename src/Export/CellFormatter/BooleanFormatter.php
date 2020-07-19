<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\CellFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BooleanFormatter implements CellFormatterInterface
{
    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value)
    {
        if (null === $value) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        if (!\is_bool($value)) {
            throw new \InvalidArgumentException('Unsupported value given, only boolean is supported');
        }

        /*
        if (true === $value) {
            $value = '=TRUE()';
        } else {
            $value = '=FALSE()';
        }
        */

        $sheet->setCellValueByColumnAndRow($column, $row, $value);
    }
}
