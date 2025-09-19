<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Export\ColumnConverter;
use App\Export\Package\Column;
use App\Export\Package\SpreadsheetPackage;
use App\Export\TemplateInterface;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class AbstractSpreadsheetRenderer
{
    private bool $internal = false;

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): void
    {
        $this->internal = $internal;
    }

    protected function getFileResponse(string $file, string $filename, string $contentType): BinaryFileResponse
    {
        $response = new BinaryFileResponse($file);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', $disposition);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    protected function writeSpreadsheet(ColumnConverter $converter, TemplateInterface $template, SpreadsheetPackage $spreadsheetPackage, array $exportItems, TimesheetQuery $query): void
    {
        /** @var array<Column> $columns */
        $columns = $converter->getColumns($template, $query);
        $spreadsheetPackage->setColumns($columns);

        $currentRow = 1;
        foreach ($exportItems as $exportItem) {
            $cells = [];
            foreach ($columns as $column) {
                $cells[] = $column->getValue($exportItem);
            }
            $spreadsheetPackage->addRow($cells);
            $currentRow++;
        }

        if ($currentRow > 1) {
            $totalColumns = ['duration', 'rate', 'internalRate'];
            // that should be enough for the near future: the number of array entries must cover the max number of columns
            $columnNames = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
                'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
            ];
            $totalRow = [];
            $totalColumn = 1;
            foreach ($columns as $column) {
                $formula = null;
                if (\in_array($column->getName(), $totalColumns)) {
                    $columnName = $columnNames[$totalColumn - 1];
                    $formula = \sprintf('=SUBTOTAL(9,%s2:%s%s)', $columnName, $columnName, $currentRow);
                }
                $totalRow[] = $formula;
                $totalColumn++;
            }

            $spreadsheetPackage->addRow($totalRow, ['totals' => true]);
        }

        $spreadsheetPackage->save();
    }
}
