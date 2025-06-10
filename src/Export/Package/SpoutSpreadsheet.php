<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package;

use App\Constants;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use Symfony\Contracts\Translation\TranslatorInterface;

class SpoutSpreadsheet implements SpreadsheetPackage
{
    /** @var array<int, Style|null> */
    private array $styles = [];

    public function __construct(
        private readonly WriterInterface $writer,
        private readonly TranslatorInterface $translator,
        private readonly ?string $locale = null,
    )
    {
        $this->writer->setCreator(Constants::SOFTWARE);
    }

    /**
     * @param array<Column> $columns
     */
    public function setColumns(array $columns): void
    {
        $columnCounter = \count($columns);
        if ($columnCounter === 0) {
            throw new \InvalidArgumentException('At least one column is required');
        }

        $tmp = [];
        $i = 0;
        foreach ($columns as $column) {
            $title = $this->translator->trans($column->getHeader(), [], null, $this->locale);
            $tmp[] = Cell::fromValue($title);
            $style = null;
            if (($format = $column->getFormat()) !== null) {
                $style = (new Style())->setFormat($format);
            }
            $this->styles[$i++] = $style;
        }

        $style = new Style();
        $style->setShouldWrapText(false);
        $style->setShouldShrinkToFit(true);
        $style->setBackgroundColor('EEEEEE');
        $style->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)));
        $style->setFontBold();

        if ($this->writer instanceof XLSXWriter) {
            $autoFilter = new AutoFilter(0, 1, $columnCounter - 1, 1);
            $this->writer->getCurrentSheet()->setAutoFilter($autoFilter);

            $options = $this->writer->getOptions();

            $i = 1;
            foreach ($columns as $column) {
                $width = match($column->getColumnWidth()) {
                    ColumnWidth::SMALL => 10,
                    ColumnWidth::MEDIUM => 30,
                    ColumnWidth::LARGE => 50,
                    default => 15,
                };
                $options->setColumnWidth($width, $i++);
            }
        }

        $this->writer->addRow(new Row($tmp, $style));
    }

    /**
     * @param array<int, mixed> $columns
     * @param array<string, mixed> $options
     */
    public function addRow(array $columns, array $options = []): void
    {
        $style = new Style();
        $style->setShouldWrapText(false);
        $style->setShouldShrinkToFit(true);

        if (\array_key_exists('totals', $options) && $options['totals'] === true) {
            if ($this->writer instanceof CSVWriter) {
                return;
            }
            $style->setBorder(new Border(new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)));
            $style->setFontBold();
        }

        $tmp = [];
        $i = 0;
        foreach ($columns as $column) {
            $tmp[] = Cell::fromValue($column, $this->styles[$i++]); // @phpstan-ignore argument.type
        }

        $this->writer->addRow(new Row($tmp, $style));
    }

    public function open(string $filename): void
    {
        $this->writer->openToFile($filename);

        if ($this->writer instanceof AbstractWriterMultiSheets) {
            $sheetView = new SheetView();

            // deactivated, because the column order is now configurable
            //$sheetView->setFreezeColumn('D');
            //$sheetView->setFreezeRow(2);

            $this->writer->getCurrentSheet()->setSheetView($sheetView);
        }
    }

    public function save(): void
    {
        $this->writer->close();
    }
}
