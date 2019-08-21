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

    protected function setFormattedDateTime(Worksheet $sheet, $column, $row, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        // TODO find proper format code
        $dateValue = $this->dateExtension->dateShort($date) . ' ' . $this->dateExtension->time($date);
        $sheet->setCellValueByColumnAndRow($column, $row, $dateValue);
    }

    protected function setFormattedDate(Worksheet $sheet, $column, $row, ?DateTime $date)
    {
        if (null === $date) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        // TODO find proper format code
        $dateValue = $this->dateExtension->dateShort($date);
        $sheet->setCellValueByColumnAndRow($column, $row, $dateValue);
    }

    protected function setDurationTotal(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate)
    {
        // TODO find proper format code
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=SUM(%s:%s)', $startCoordinate, $endCoordinate));
    }

    protected function setDuration(Worksheet $sheet, $column, $row, $duration)
    {
        // TODO find proper format code
        $sheet->setCellValueByColumnAndRow($column, $row, $duration);
    }

    protected function setRateTotal(Worksheet $sheet, $column, $row, $startCoordinate, $endCoordinate)
    {
        $sheet->setCellValueByColumnAndRow($column, $row, sprintf('=SUM(%s:%s)', $startCoordinate, $endCoordinate));
    }
}
