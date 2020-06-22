<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\InvoiceTemplate;

class InvoiceQuery extends TimesheetQuery
{
    /**
     * @var InvoiceTemplate
     */
    private $template;
    /**
     * @var bool
     */
    private $markAsExported = false;

    public function getTemplate(): ?InvoiceTemplate
    {
        return $this->template;
    }

    public function setTemplate(InvoiceTemplate $template): InvoiceQuery
    {
        $this->template = $template;

        return $this;
    }

    public function isMarkAsExported(): bool
    {
        return $this->markAsExported;
    }

    public function setMarkAsExported(bool $markAsExported): InvoiceQuery
    {
        $this->markAsExported = $markAsExported;

        return $this;
    }
}
