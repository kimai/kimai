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
    private $renderer;
    /**
     * @var bool
     */
    private $markAsExported = false;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'order' => ExportQuery::ORDER_ASC,
            'state' => ExportQuery::STATE_STOPPED,
            'exported' => ExportQuery::STATE_NOT_EXPORTED,
        ]);
    }

    public function getRenderer(): ?string
    {
        return $this->renderer;
    }

    public function setRenderer(string $renderer): ExportQuery
    {
        $this->renderer = $renderer;

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
