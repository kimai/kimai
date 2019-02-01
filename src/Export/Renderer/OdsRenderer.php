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

class OdsRenderer extends AbstractSpreadsheetRenderer implements RendererInterface
{

    public function getFileExtension(): string
    {
        return '.ods';
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
     * @return bool|string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function saveSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'kimai-export-ods');
        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save($filename);

        return $filename;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'ods';
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return 'ods';
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'ods';
    }
}
