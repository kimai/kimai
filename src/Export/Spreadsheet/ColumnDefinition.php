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
    private $label;
    private $type;
    private $accessor;

    public function __construct(string $label, string $type, callable $accessor)
    {
        $this->label = $label;
        $this->type = $type;
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
