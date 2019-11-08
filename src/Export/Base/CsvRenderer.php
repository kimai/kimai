<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CsvRenderer extends AbstractSpreadsheetRenderer
{
    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return '.csv';
    }

    /**
     * @return string
     */
    protected function getContentType(): string
    {
        return 'text/csv';
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return string
     * @throws \Exception
     */
    protected function saveSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'kimai-export-csv');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save($filename);

        return $filename;
    }

    public function getId(): string
    {
        return 'csv';
    }

    protected function setDuration(Worksheet $sheet, $column, $row, $duration)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=%s', $duration));
    }
}
