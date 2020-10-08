<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class AdvancedValueBinder extends DefaultValueBinder implements IValueBinder
{
    /**
     * Bind value to a cell.
     *
     * @param Cell $cell Cell to bind value to
     * @param mixed $value Value to bind in cell
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return bool
     */
    public function bindValue(Cell $cell, $value = null)
    {
        if (\is_string($value)) {
            $value = StringHelper::sanitizeUTF8($value);
        }

        $dataType = parent::dataTypeForValue($value);

        if ($dataType === DataType::TYPE_STRING && !$value instanceof RichText) {
            // Check for newline character "\n"
            if (strpos($value, "\n") !== false) {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
                $cell->getWorksheet()->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);

                $amount = substr_count($value, "\n");
                $dimension = $cell->getWorksheet()->getRowDimension($cell->getRow());
                if ($dimension->getRowHeight() !== -1) {
                    $defaultHeight = $cell->getWorksheet()->getDefaultRowDimension()->getRowHeight();
                    $dimension->setRowHeight($defaultHeight * ($amount + 1));
                }

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }
}
