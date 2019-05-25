<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\RendererInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class XlsxRenderer extends AbstractSpreadsheetRenderer implements RendererInterface
{
    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return '.xlsx';
    }

    /**
     * @return string
     */
    protected function getContentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return string
     * @throws \Exception
     */
    protected function saveSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'kimai-export-xlsx');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filename);

        return $filename;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'xlsx';
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return 'xlsx';
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'xlsx';
    }
}
