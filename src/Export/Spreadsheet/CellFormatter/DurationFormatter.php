<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\CellFormatter;

use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class DurationFormatter implements CellFormatterInterface
{
    public const DURATION_FORMAT = '[hh]:mm';

    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void
    {
        if (null === $value) {
            $value = 0;
        }

        if (!\is_int($value)) {
            throw new \InvalidArgumentException('Unsupported value given, only int is supported');
        }

        $sheet->setCellValue(CellAddress::fromColumnAndRow($column, $row), sprintf('=%s/86400', $value));
        $sheet->getStyle(CellAddress::fromColumnAndRow($column, $row))->getNumberFormat()->setFormatCode(self::DURATION_FORMAT);
    }
}
