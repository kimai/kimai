<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

class ExportQuery extends TimesheetQuery
{
    /**
     * @var string
     */
    private $type;
    /**
     * @var bool
     */
    private $markAsExported = false;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): ExportQuery
    {
        $this->type = $type;

        return $this;
    }

    public function isMarkAsExported(): bool
    {
        return $this->markAsExported;
    }

    public function setMarkAsExported(bool $markAsExported): ExportQuery
    {
        $this->markAsExported = $markAsExported;

        return $this;
    }
}
