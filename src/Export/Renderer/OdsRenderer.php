<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\RendererInterface;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class OdsRenderer extends AbstractSpreadsheetRenderer implements RendererInterface
{
    /**
     * @return string
     */
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
     * @return string
     * @throws \Exception
     */
    protected function saveSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'kimai-export-ods');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

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

    protected function setFormattedDateTime(Worksheet $sheet, $entryHeaderColumn, $entryHeaderRow, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, '');

            return;
        }

        $dateValue = $this->dateExtension->dateShort($date) . ' ' . $this->dateExtension->time($date);
        $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $dateValue);
    }

    protected function setFormattedDate(Worksheet $sheet, $entryHeaderColumn, $entryHeaderRow, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, '');

            return;
        }

        $dateValue = $this->dateExtension->dateShort($date);
        $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $dateValue);
    }

    protected function setDurationTotalFormula(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate, $durationTotal)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, $durationTotal);
        $sheet->getCellByColumnAndRow($column, $row)->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getCellByColumnAndRow($column, $row)->getStyle()->getFont()->setBold(true);
    }
}
