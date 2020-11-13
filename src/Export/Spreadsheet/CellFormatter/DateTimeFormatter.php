<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\CellFormatter;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DateTimeFormatter implements CellFormatterInterface
{
    public const DATETIME_FORMAT = 'yyyy-mm-dd hh:mm';

    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void
    {
        if (null === $value) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        if (!$value instanceof \DateTime) {
            throw new \InvalidArgumentException('Unsupported value given, only DateTime is supported');
        }

        $sheet->setCellValueByColumnAndRow($column, $row, Date::PHPToExcel($value));
        $sheet->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode(self::DATETIME_FORMAT);
    }
}
