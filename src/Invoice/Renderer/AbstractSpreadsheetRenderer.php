<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Invoice\InvoiceModel;
use App\Model\InvoiceDocument;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
abstract class AbstractSpreadsheetRenderer extends AbstractRenderer
{
    /**
     * Saves the Spreadhseet and returns the filename.
     */
    abstract protected function saveSpreadsheet(Spreadsheet $spreadsheet): string;

    /**
     * Render the given InvoiceDocument with the data from the InvoiceModel.
     *
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     * @throws \Exception
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $spreadsheet = IOFactory::load($document->getFilename());
        $worksheet = $spreadsheet->getActiveSheet();
        $entries = $model->getCalculator()->getEntries();
        $sheetReplacer = $model->toArray();
        $invoiceItemCount = \count($entries);
        if ($invoiceItemCount > 1) {
            $this->addTemplateRows($worksheet, $invoiceItemCount);
        }

        // cleanup the title, PHP Office doesn't allow arbitrary strings
        $title = substr($model->getTemplate()->getTitle(), 0, 31);
        foreach (Worksheet::getInvalidCharacters() as $char) {
            $title = str_replace($char, ' ', $title);
        }

        $worksheet->setTitle($title);

        $entryRow = 0;

        Cell::setValueBinder(new AdvancedValueBinder());

        foreach ($worksheet->getRowIterator() as $row) {
            $sheetValues = false;
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if ($value === null) {
                    continue;
                }
                $replacer = null;
                $firstReplacerPos = stripos($value, '${');
                if ($firstReplacerPos === false) {
                    continue;
                }

                if (stripos($value, '${entry.') !== false) {
                    if ($sheetValues === false && isset($entries[$entryRow])) {
                        $sheetValues = $model->itemToArray($entries[$entryRow]);
                    }
                    $replacer = $sheetValues;
                } elseif (stripos($value, '${') !== false) {
                    $replacer = $sheetReplacer;
                }

                if (empty($replacer)) {
                    continue;
                }

                // we can have mixed cell content, which makes it much more complicated
                // templates can have a formula, which utilize the timesheet records content like this:
                // =IF("${entry.category}"="work";"${entry.activity}";"")
                $contentLooksLikeFormula = false;
                foreach ($replacer as $key => $content) {
                    $searchKey = '${' . $key . '}';
                    if (stripos($value, $searchKey) === false) {
                        continue;
                    }
                    if (\is_string($content) && str_starts_with($content, '=')) {
                        $contentLooksLikeFormula = true;
                    }
                    $value = str_replace($searchKey, $content ?? '', $value);
                }

                if ($contentLooksLikeFormula && $firstReplacerPos === 0) {
                    // see https://github.com/kimai/kimai/pull/2054
                    $cell->setValueExplicit($value, DataType::TYPE_STRING);
                } else {
                    $cell->setValue($value);
                }
            }

            if ($sheetValues !== false && $entryRow < $invoiceItemCount - 1) {
                $entryRow++;
            }
        }

        $filename = $this->saveSpreadsheet($spreadsheet);
        $userFilename = $this->buildFilename($model) . '.' . $document->getFileExtension();

        return $this->getFileResponse($filename, $userFilename);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addTemplateRows(Worksheet $worksheet, int $invoiceItemCount): void
    {
        $startRow = null;
        $rowCounter = 0;

        foreach ($worksheet->getRowIterator() as $row) {
            $cellCounter = 0;
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if ($value !== null && stripos($value, '${entry.') !== false) {
                    $startRow = $row->getRowIndex();
                    $worksheet->insertNewRowBefore($startRow + 1, $invoiceItemCount - 1);
                    break 2;
                }

                if ($cellCounter++ >= 10) {
                    break;
                }
            }

            if ($rowCounter++ >= 100) {
                break;
            }
        }

        if ($startRow === null) {
            throw new \Exception('Invalid invoice document, no template row found.');
        }

        // fill up all new rows with template replacer
        $templateRow = $startRow;
        $iterator = $worksheet->getRowIterator($templateRow, $templateRow + 1);

        $templateColumns = [];

        $tmpRow = $iterator->current();
        foreach ($tmpRow->getCellIterator() as $cell) {
            $templateColumns[$cell->getColumn()] = $cell->getValue();
        }

        $iterator = $worksheet->getRowIterator($startRow, $startRow + $invoiceItemCount - 1);
        foreach ($iterator as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->setValue($templateColumns[$cell->getColumn()]);
            }
        }
    }
}
