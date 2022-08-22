<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

final class ColumnDefinition
{
    private $accessor;

    public function __construct(private string $label, private string $type, callable $accessor)
    {
        $this->accessor = $accessor;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccessor(): callable
    {
        return $this->accessor;
    }
}
