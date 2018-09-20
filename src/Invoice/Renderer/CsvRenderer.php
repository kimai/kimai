<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Invoice\RendererInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CsvRenderer extends AbstractSpreadsheetRenderer implements RendererInterface
{
    /**
     * @return string[]
     */
    protected function getFileExtensions()
    {
        return ['.csv'];
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        return 'text/csv';
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return bool|string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function saveSpreadsheet(Spreadsheet $spreadsheet)
    {
        $filename = tempnam(sys_get_temp_dir(), 'kimai-csv');
        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save($filename);

        return $filename;
    }
}
