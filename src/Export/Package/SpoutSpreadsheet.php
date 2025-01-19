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
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Entity\SheetView;

class SpoutSpreadsheet implements SpreadsheetPackage
{
    private Style $dateStyle;

    public function __construct(private readonly WriterInterface $writer)
    {
        $this->writer->setCreator(Constants::SOFTWARE);
        $this->dateStyle = (new Style())->setFormat('yyyy-mm-dd');
    }

    /**
     * @param array<string> $columns
     */
    public function setHeader(array $columns): void
    {
        $tmp = [];
        foreach ($columns as $column) {
            $tmp[] = Cell::fromValue($column);
        }

        $style = new Style();
        $style->setShouldWrapText(false);
        $style->setShouldShrinkToFit(true);
        $style->setBackgroundColor('EEEEEE');
        $style->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)));
        $style->setFontBold();

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
            if ($this->writer instanceof Writer) {
                return;
            }
            $style->setBorder(new Border(new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)));
            $style->setFontBold();
        }

        $tmp = [];
        foreach ($columns as $column) {
            if ($column instanceof \DateTimeInterface) {
                $tmp[] = Cell::fromValue($column, $this->dateStyle);
            } else {
                $tmp[] = Cell::fromValue($column); // @phpstan-ignore argument.type
            }
        }

        $this->writer->addRow(new Row($tmp, $style));
    }

    public function open(string $filename): void
    {
        $this->writer->openToFile($filename);

        if ($this->writer instanceof AbstractWriterMultiSheets) {
            $sheetView = new SheetView();
            $sheetView->setFreezeColumn('D');
            $sheetView->setFreezeRow(2);

            $this->writer->getCurrentSheet()->setSheetView($sheetView);
        }
    }

    public function save(): void
    {
        $this->writer->close();
    }
}
