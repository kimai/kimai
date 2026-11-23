<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

use App\Utils\StringHelper;

final class TextFormatter implements CellFormatterInterface
{
    /**
     * Excel has a max length per cell
     *
     * See OpenSpout\Writer\XLSX\Manager\WorksheetManager::MAX_CHARACTERS_PER_CELL
     */
    public const MAX_CHARACTERS_PER_CELL = 32767;

    public function __construct(private readonly bool $sanitizeDde)
    {
    }

    public function formatValue(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        if ($this->sanitizeDde) {
            $value = StringHelper::sanitizeDDE($value);
        }

        return StringHelper::ensureMaxLength($value, self::MAX_CHARACTERS_PER_CELL);
    }
}
