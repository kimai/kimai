<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package;

use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PhpOfficeSpreadsheet implements SpreadsheetPackage
{
    private ?Spreadsheet $spreadsheet;
    private ?Worksheet $worksheet;
    private int $currentRow = 1;
    private ?string $filename = null;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public function open(string $filename): void
    {
        $this->filename = $filename;
    }

    public function save(): void
    {
        if ($this->filename === null) {
            throw new \Exception('Need to call open() first before save()');
        }

        if ($this->spreadsheet === null || $this->worksheet === null) {
            throw new \Exception('Cannot re-use spreadsheet after calling save()');
        }

        $sheet = $this->worksheet;
        // Store expensive calculations for later
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Enable auto filter for header row
        $sheet->setAutoFilter('A1:' . $highestColumn . '1');

        // Freeze first row and date & time columns for easier navigation
        $sheet->freezePane('D2');

        foreach ($sheet->getColumnIterator() as $columnName => $column) {
            // We default to a reasonable auto-width decided by the client,
            // sadly ->getDefaultColumnDimension() is not supported so it needs
            // to be specific about what column should be auto sized.
            $col = $sheet->getColumnDimension($columnName);

            // If no other width is specified (which defaults to -1)
            if ((int) $col->getWidth() === -1) {
                $col->setAutoSize(true);
            }
        }

        // Text inside cells should be top left
        $sheet
            ->getStyle('A2:' . $highestColumn . $highestRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $writer->save($this->filename);

        $this->spreadsheet = null;
        $this->worksheet = null;
    }

    /**
     * @param array<string> $columns
     */
    public function setHeader(array $columns): void
    {
        if ($this->worksheet === null) {
            throw new \Exception('Cannot re-use spreadsheet after calling save()');
        }

        $counter = 1;
        foreach ($columns as $column) {
            $pos = CellAddress::fromColumnAndRow($counter, 1);
            $this->worksheet->setCellValue($pos, $column);
            $style = $this->worksheet->getStyle($pos);
            $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $style->getFont()->setBold(true);

            $counter++;
        }

        $this->currentRow++;
    }

    /**
     * @param array<int, mixed> $columns
     * @param array<string, mixed> $options
     */
    public function addRow(array $columns, array $options = []): void
    {
        if ($this->worksheet === null) {
            throw new \Exception('Cannot re-use spreadsheet after calling save()');
        }

        $counter = 1;
        foreach ($columns as $column) {
            $this->worksheet->setCellValue(CellAddress::fromColumnAndRow($counter, $this->currentRow), $column);

            if (\array_key_exists('totals', $options) && $options['totals'] === true) {
                $style = $this->worksheet->getStyle(CellAddress::fromColumnAndRow($counter, $this->currentRow));
                $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $style->getFont()->setBold(true);
            }
            $counter++;
        }

        $this->currentRow++;
    }
}
