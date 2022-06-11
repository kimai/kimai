<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\InvoiceTemplate;

/**
 * Find items (eg timesheets) for creating a new invoice.
 */
class InvoiceQuery extends TimesheetQuery
{
    /**
     * @var InvoiceTemplate
     */
    private $template;
    /**
     * @var bool
     */
    private $markAsExported = true;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'order' => InvoiceQuery::ORDER_ASC,
            'exported' => InvoiceQuery::STATE_NOT_EXPORTED,
            'state' => self::STATE_STOPPED,
            'billable' => true,
            'markAsExported' => true,
        ]);
    }

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
