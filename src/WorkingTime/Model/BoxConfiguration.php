<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

final class BoxConfiguration
{
    private bool $decimal = false;
    private bool $collapsed = false;

    public function setDecimal(bool $decimal): void
    {
        $this->decimal = $decimal;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    public function isDecimal(): bool
    {
        return $this->decimal;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }
}
