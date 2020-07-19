<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\CellFormatter;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanFormatter implements CellFormatterInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value)
    {
        if (null === $value) {
            $sheet->setCellValueByColumnAndRow($column, $row, '');

            return;
        }

        if (!\is_bool($value)) {
            throw new \InvalidArgumentException('Unsupported value given, only boolean is supported');
        }

        if (true === $value) {
            $value = $this->translator->trans('yes');
        } else {
            $value = $this->translator->trans('no');
        }

        $sheet->setCellValueByColumnAndRow($column, $row, $value);
    }
}
