<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

use App\Export\Spreadsheet\CellFormatter\ArrayFormatter;
use App\Export\Spreadsheet\CellFormatter\BooleanFormatter;
use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\DateFormatter;
use App\Export\Spreadsheet\CellFormatter\DateTimeFormatter;
use App\Export\Spreadsheet\CellFormatter\DurationFormatter;
use App\Export\Spreadsheet\CellFormatter\TimeFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class SpreadsheetExporter
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var CellFormatterInterface[]
     */
    private $formatter = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->registerCellFormatter('datetime', new DateTimeFormatter());
        $this->registerCellFormatter('date', new DateFormatter());
        $this->registerCellFormatter('time', new TimeFormatter());
        $this->registerCellFormatter('duration', new DurationFormatter());
        $this->registerCellFormatter('boolean', new BooleanFormatter());
        $this->registerCellFormatter('array', new ArrayFormatter());
    }

    public function registerCellFormatter(string $type, CellFormatterInterface $formatter)
    {
        $this->formatter[$type] = $formatter;
    }

    /**
     * @param ColumnDefinition[] $columns
     * @param array $entries
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(array $columns, array $entries): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set default row height to automatic, so we can specify wrap text columns later on
        // without bloating the output file as we would need to store stylesheet info for every cell.
        // LibreOffice is still not considering this flag, @see https://github.com/PHPOffice/PHPExcel/issues/588
        // with no solution implemented so nothing we can do about it there.
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        foreach ($columns as $settings) {
            $sheet->setCellValueByColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow, $this->translator->trans($settings->getLabel()));
        }

        $entryHeaderRow = $recordsHeaderRow + 1;

        foreach ($entries as $entry) {
            $entryHeaderColumn = 1;

            foreach ($columns as $settings) {
                $value = \call_user_func($settings->getAccessor(), $entry);

                if (!\array_key_exists($settings->getType(), $this->formatter)) {
                    $sheet->setCellValueByColumnAndRow($entryHeaderColumn, $entryHeaderRow, $value);
                } else {
                    $formatter = $this->formatter[$settings->getType()];
                    $formatter->setFormattedValue($sheet, $entryHeaderColumn, $entryHeaderRow, $value);
                }

                $entryHeaderColumn++;
            }

            $entryHeaderRow++;
        }

        return $spreadsheet;
    }
}
