<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

trait BillableTrait
{
    /**
     * @var bool|null
     */
    private $billable = null;

    public function getBillable(): ?bool
    {
        return $this->billable;
    }

    public function isBillable(): bool
    {
        return $this->billable === true;
    }

    public function isNotBillable(): bool
    {
        return $this->billable === false;
    }

    public function isIgnoreBillable(): bool
    {
        return $this->billable === null;
    }

    public function setBillable(?bool $isBillable): void
    {
        $this->billable = $isBillable;
    }
}
